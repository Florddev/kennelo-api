<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\ActivityPermissionEnum;
use App\Enums\AvailabilityStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\FinancialOperationTypeEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\WeekDayEnum;
use App\Jobs\ExpireBookingJob;
use App\Jobs\SendBookingReminderJob;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\ActivityCycleSetting;
use App\Models\ActivityCycleSettingPrice;
use App\Models\Booking;
use App\Models\BookingThread;
use App\Models\Conversation;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use App\Services\Activity\ActivityCycleService;
use App\Services\Conversation\ConversationService;
use App\Services\Finance\FinancialJournalService;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use App\Services\Stripe\StripeCustomerService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class BookingService
{
    public function __construct(
        private ConversationService $conversationService,
        private StripeClient $stripe,
        private StripeCustomerService $customerService,
        private ActivityCycleService $cycleService,
        private NotificationService $notifications,
        private NotificationRecipientResolver $recipients,
        private FinancialJournalService $journal
    ) {}

    public function getUserBookings(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Booking::with(['activity', 'pets', 'services'])
            ->where('user_id', $user->id)
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate($perPage);
    }

    public function getActivityBookings(Activity $activity, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Booking::with(['user', 'pets', 'services'])
            ->where('activity_id', $activity->id)
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date_from']), fn ($q) => $q->where('check_in_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($q) => $q->where('check_out_date', '<=', $filters['date_to']))
            ->latest()
            ->paginate($perPage);
    }

    public function create(User $user, array $data, string $paymentMethodId, bool $savePaymentMethod = false): Booking
    {
        $activity = Activity::findOrFail($data['activity_id']);

        $activity->loadMissing('manager');

        $this->assertHostCanAcceptBookings($activity);

        $checkIn = Carbon::parse($data['check_in_date']);
        $checkOut = Carbon::parse($data['check_out_date']);

        $pets = Pet::whereIn('id', $data['pet_ids'])->get();
        $services = ! empty($data['service_ids'])
            ? Service::whereIn('id', $data['service_ids'])->get()
            : collect();

        [$booking, $totalPrice] = DB::transaction(function () use ($user, $data, $activity, $checkIn, $checkOut, $pets, $services): array {
            Activity::whereKey($activity->id)->lockForUpdate()->first();

            $this->validateAvailability($activity, $checkIn, $checkOut);

            [$totalPrice, $serviceFee, $platformFee, $activityAmount, $petPivots, $servicePivots] =
                $this->priceBooking($activity, $pets, $services, $checkIn, $checkOut);

            $booking = Booking::create([
                'user_id' => $user->id,
                'activity_id' => $activity->id,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'total_price' => $totalPrice,
                'service_fee' => $serviceFee,
                'platform_fee' => $platformFee,
                'activity_amount' => $activityAmount,
                'status' => BookingStatusEnum::PENDING,
                'special_requests' => $data['special_requests'] ?? null,
            ]);

            $booking->pets()->attach($petPivots);

            if ($services->isNotEmpty()) {
                $booking->services()->attach($servicePivots);
            }

            return [$booking->load([
                'activity.address',
                'activity.manager',
                'activity.collaborators',
                'pets',
                'services',
            ]), $totalPrice];
        });

        try {
            $pi = $this->createConfirmedPaymentIntent($booking, $totalPrice, $user, $paymentMethodId, $savePaymentMethod);
        } catch (CardException|InvalidRequestException $e) {
            $booking->update([
                'status' => BookingStatusEnum::CANCELLED,
                'payment_status' => PaymentStatusEnum::FAILED,
            ]);

            throw ValidationException::withMessages([
                'payment_method_id' => ['The payment could not be processed: '.$e->getMessage()],
            ]);
        }

        $booking->update([
            'stripe_payment_intent_id' => $pi->id,
            'stripe_charge_id' => $pi->latest_charge ?? null,
            'stripe_transfer_group' => 'booking_'.$booking->id,
            'payment_status' => $this->mapPaymentStatus($pi->status),
        ]);

        $this->journal->record(
            FinancialOperationTypeEnum::AUTHORIZE,
            $booking,
            (string) $totalPrice,
            $pi->id,
        );

        $booking->setAttribute('client_secret', $pi->client_secret);

        $this->conversationService->getOrCreateForBooking($user, $booking);

        $this->notifications->notify(
            $this->recipients->forActivity($activity, ActivityPermissionEnum::MANAGE_BOOKINGS),
            NotificationTypeEnum::BOOKING_CREATED,
            [
                'booking_id' => $booking->id,
                'activity_id' => $activity->id,
                'activity_name' => $activity->name,
                'user_id' => $user->id,
                'check_in_date' => $booking->check_in_date->toDateString(),
                'check_out_date' => $booking->check_out_date->toDateString(),
            ],
        );

        return $booking->load([
            'activity.address',
            'activity.manager',
            'activity.collaborators',
            'pets',
            'services',
        ]);
    }

    /**
     * @return array{check_in_date: string, check_out_date: string, nights: int, total_price: string, service_fee: string, platform_fee: string, activity_amount: string, pets: array<int, array<string, mixed>>, services: array<int, array<string, mixed>>}
     */
    public function quote(array $data): array
    {
        $activity = Activity::findOrFail($data['activity_id']);

        $activity->loadMissing('manager');

        $this->assertHostCanAcceptBookings($activity);

        $checkIn = Carbon::parse($data['check_in_date']);
        $checkOut = Carbon::parse($data['check_out_date']);
        $nights = (int) $checkIn->diffInDays($checkOut);

        $pets = Pet::whereIn('id', $data['pet_ids'])->get();
        $services = ! empty($data['service_ids'])
            ? Service::whereIn('id', $data['service_ids'])->get()
            : collect();

        $this->validateAvailability($activity, $checkIn, $checkOut);

        [$totalPrice, $serviceFee, $platformFee, $activityAmount, $petPivots, $servicePivots] =
            $this->priceBooking($activity, $pets, $services, $checkIn, $checkOut);

        return [
            'check_in_date' => $checkIn->toDateString(),
            'check_out_date' => $checkOut->toDateString(),
            'nights' => $nights,
            'total_price' => $totalPrice,
            'service_fee' => $serviceFee,
            'platform_fee' => $platformFee,
            'activity_amount' => $activityAmount,
            'pets' => $pets->map(fn (Pet $pet): array => [
                'id' => $pet->id,
                'name' => $pet->name,
                'animal_type_id' => $pet->animal_type_id,
                'price_per_night' => $petPivots[$pet->id]['price_per_night'],
                'number_of_nights' => $petPivots[$pet->id]['number_of_nights'],
                'subtotal' => $petPivots[$pet->id]['subtotal'],
            ])->values()->all(),
            'services' => $services->map(fn (Service $service): array => [
                'id' => $service->id,
                'name' => $service->name,
                'quantity' => $servicePivots[$service->id]['quantity'],
                'unit_price' => $servicePivots[$service->id]['unit_price'],
                'subtotal' => $servicePivots[$service->id]['subtotal'],
            ])->values()->all(),
        ];
    }

    private function createConfirmedPaymentIntent(
        Booking $booking,
        string $totalPrice,
        User $user,
        string $paymentMethodId,
        bool $savePaymentMethod
    ): PaymentIntent {
        $amountInCents = (int) bcmul($totalPrice, '100', 0);
        $currency = (string) config('services.stripe.currency', 'eur');
        $customerId = $this->customerService->getOrCreateCustomer($user);

        $payload = [
            'amount' => $amountInCents,
            'currency' => $currency,
            'customer' => $customerId,
            'payment_method' => $paymentMethodId,
            'confirm' => true,
            'off_session' => false,
            'capture_method' => 'manual',
            'payment_method_types' => ['card'],
            'transfer_group' => 'booking_'.$booking->id,
            'metadata' => [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'activity_id' => $booking->activity_id,
            ],
        ];

        if ($savePaymentMethod) {
            $payload['setup_future_usage'] = 'off_session';
        }

        return $this->stripe->paymentIntents->create($payload);
    }

    private function mapPaymentStatus(string $stripeStatus): PaymentStatusEnum
    {
        return match ($stripeStatus) {
            'succeeded' => PaymentStatusEnum::SUCCEEDED,
            'requires_capture' => PaymentStatusEnum::REQUIRES_CAPTURE,
            'requires_action', 'requires_confirmation' => PaymentStatusEnum::REQUIRES_ACTION,
            'processing' => PaymentStatusEnum::PROCESSING,
            'canceled' => PaymentStatusEnum::CANCELED,
            default => PaymentStatusEnum::PENDING,
        };
    }

    public function cancel(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $this->assertStatus($booking, [BookingStatusEnum::PENDING, BookingStatusEnum::CONFIRMED], 'cancel');

            $this->releaseAuthorization($booking);

            $booking->update(['status' => BookingStatusEnum::CANCELLED]);

            $this->journal->record(FinancialOperationTypeEnum::STATUS_CHANGE, $booking, null, null, [
                'status' => BookingStatusEnum::CANCELLED->value,
            ]);

            $booking->loadMissing('activity');

            if ($booking->activity !== null) {
                $this->notifications->notify(
                    $this->recipients->forActivity($booking->activity, ActivityPermissionEnum::MANAGE_BOOKINGS),
                    NotificationTypeEnum::BOOKING_CANCELLED_BY_CLIENT,
                    [
                        'booking_id' => $booking->id,
                        'activity_id' => $booking->activity_id,
                        'user_id' => $booking->user_id,
                    ],
                );
            }

            return $booking->fresh();
        });
    }

    public function confirm(Booking $booking, User $actor, ?string $message = null): Booking
    {
        $booking = DB::transaction(function () use ($booking): Booking {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $this->assertStatus($booking, [BookingStatusEnum::PENDING], 'confirm');

            $this->assertCapacityForConfirm($booking);

            try {
                $pi = $this->stripe->paymentIntents->capture($booking->stripe_payment_intent_id);
            } catch (CardException|InvalidRequestException $e) {
                $booking->update(['payment_status' => PaymentStatusEnum::FAILED]);
                $this->journal->record(
                    FinancialOperationTypeEnum::CAPTURE_FAILED,
                    $booking,
                    (string) $booking->total_price,
                    $booking->stripe_payment_intent_id,
                    ['error' => $e->getMessage()],
                );
                $this->notifyBookingUser($booking, NotificationTypeEnum::PAYMENT_FAILED);

                throw ValidationException::withMessages([
                    'payment' => ['The payment could not be captured: '.$e->getMessage()],
                ]);
            }

            $booking->update([
                'status' => BookingStatusEnum::CONFIRMED,
                'payment_status' => PaymentStatusEnum::SUCCEEDED,
                'paid_at' => Carbon::now(),
                'stripe_charge_id' => $pi->latest_charge ?? $booking->stripe_charge_id,
            ]);

            $this->journal->record(
                FinancialOperationTypeEnum::CAPTURE,
                $booking,
                (string) $booking->total_price,
                $pi->latest_charge ?? $booking->stripe_charge_id,
            );

            return $booking;
        });

        $this->sendBookingReferenceIfConversationExists($booking, $actor, $message);

        $this->notifyBookingUser($booking, NotificationTypeEnum::BOOKING_CONFIRMED);

        return $booking->fresh();
    }

    public function complete(Booking $booking): Booking
    {
        $this->assertStatus($booking, [BookingStatusEnum::CONFIRMED, BookingStatusEnum::IN_PROGRESS], 'complete');

        $booking->update(['status' => BookingStatusEnum::COMPLETED]);

        $this->journal->record(FinancialOperationTypeEnum::STATUS_CHANGE, $booking, null, null, [
            'status' => BookingStatusEnum::COMPLETED->value,
        ]);

        $this->notifyBookingUser($booking, NotificationTypeEnum::BOOKING_COMPLETED);

        return $booking->fresh();
    }

    public function releaseAuthorization(Booking $booking): Booking
    {
        if ($booking->payment_status === PaymentStatusEnum::SUCCEEDED && $booking->stripe_charge_id) {
            $refund = $this->stripe->refunds->create([
                'charge' => $booking->stripe_charge_id,
                'metadata' => ['booking_id' => $booking->id],
            ]);
            $booking->update([
                'stripe_refund_id' => $refund->id,
                'refunded_amount' => bcdiv((string) $refund->amount, '100', 2),
                'refunded_at' => Carbon::now(),
                'payment_status' => PaymentStatusEnum::REFUNDED,
            ]);

            $this->journal->record(
                FinancialOperationTypeEnum::REFUND,
                $booking,
                bcdiv((string) $refund->amount, '100', 2),
                $refund->id,
            );

            return $booking->fresh();
        }

        if ($booking->stripe_payment_intent_id !== null) {
            try {
                $this->stripe->paymentIntents->cancel($booking->stripe_payment_intent_id);
                $booking->update(['payment_status' => PaymentStatusEnum::CANCELED]);
                $this->journal->record(
                    FinancialOperationTypeEnum::RELEASE,
                    $booking,
                    null,
                    $booking->stripe_payment_intent_id,
                );
            } catch (InvalidRequestException $e) {
                Log::warning('Failed to release Stripe authorization for booking.', [
                    'booking_id' => $booking->id,
                    'payment_intent_id' => $booking->stripe_payment_intent_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $booking->fresh();
    }

    public function rejectByActivity(Booking $booking, User $actor, ?string $message = null): Booking
    {
        $booking = DB::transaction(function () use ($booking): Booking {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $this->assertStatus($booking, [BookingStatusEnum::PENDING], 'reject');

            $this->releaseAuthorization($booking);

            $booking->update(['status' => BookingStatusEnum::REJECTED]);

            $this->journal->record(FinancialOperationTypeEnum::STATUS_CHANGE, $booking, null, null, [
                'status' => BookingStatusEnum::REJECTED->value,
            ]);

            return $booking;
        });

        $this->sendBookingReferenceIfConversationExists($booking, $actor, $message);

        $this->notifyBookingUser($booking, NotificationTypeEnum::BOOKING_REJECTED);

        return $booking->fresh();
    }

    public function expireStalePending(): int
    {
        $threshold = Carbon::now()->subHours((int) config('booking.acceptance_window_hours', 72));

        $bookingIds = Booking::where('status', BookingStatusEnum::PENDING)
            ->where('created_at', '<=', $threshold)
            ->pluck('id');

        foreach ($bookingIds as $bookingId) {
            ExpireBookingJob::dispatch((string) $bookingId);
        }

        return $bookingIds->count();
    }

    public function expireBooking(string $bookingId): void
    {
        DB::transaction(function () use ($bookingId): void {
            $booking = Booking::where('id', $bookingId)
                ->where('status', BookingStatusEnum::PENDING)
                ->lockForUpdate()
                ->first();

            if ($booking === null) {
                return;
            }

            $this->releaseAuthorization($booking);

            $booking->update(['status' => BookingStatusEnum::EXPIRED]);

            $this->journal->record(FinancialOperationTypeEnum::STATUS_CHANGE, $booking, null, null, [
                'status' => BookingStatusEnum::EXPIRED->value,
            ]);

            $this->notifyBookingUser($booking, NotificationTypeEnum::BOOKING_EXPIRED);
        });
    }

    public function remindPendingBookings(): int
    {
        $reminderAt = Carbon::now()->subHours((int) config('booking.reminder_after_hours', 36));
        $windowStart = Carbon::now()->subHours((int) config('booking.acceptance_window_hours', 72));

        $bookingIds = Booking::where('status', BookingStatusEnum::PENDING)
            ->whereNull('reminded_at')
            ->where('created_at', '<=', $reminderAt)
            ->where('created_at', '>', $windowStart)
            ->pluck('id');

        foreach ($bookingIds as $bookingId) {
            SendBookingReminderJob::dispatch((string) $bookingId);
        }

        return $bookingIds->count();
    }

    public function remindBooking(string $bookingId): void
    {
        DB::transaction(function () use ($bookingId): void {
            $booking = Booking::where('id', $bookingId)
                ->where('status', BookingStatusEnum::PENDING)
                ->whereNull('reminded_at')
                ->lockForUpdate()
                ->first();

            if ($booking === null) {
                return;
            }

            $booking->update(['reminded_at' => Carbon::now()]);

            $booking->loadMissing('activity');

            if ($booking->activity === null) {
                return;
            }

            $this->notifications->notify(
                $this->recipients->forActivity($booking->activity, ActivityPermissionEnum::MANAGE_BOOKINGS),
                NotificationTypeEnum::BOOKING_REMINDER,
                [
                    'booking_id' => $booking->id,
                    'activity_id' => $booking->activity_id,
                    'activity_name' => $booking->activity->name,
                ],
            );
        });
    }

    private function notifyBookingUser(Booking $booking, NotificationTypeEnum $type): void
    {
        $booking->loadMissing('user', 'activity');

        if ($booking->user === null) {
            return;
        }

        $this->notifications->notify(
            $booking->user,
            $type,
            [
                'booking_id' => $booking->id,
                'activity_id' => $booking->activity_id,
                'activity_name' => $booking->activity?->name,
            ],
        );
    }

    /**
     * @return array{string, string, string, string, array<string, array{price_per_night: string, number_of_nights: int, subtotal: string}>, array<string, array{quantity: int, unit_price: string, subtotal: string}>}
     */
    private function priceBooking(
        Activity $activity,
        Collection $pets,
        Collection $services,
        Carbon $checkIn,
        Carbon $checkOut
    ): array {
        $cycles = $this->activeCyclesFor($activity);
        $requestedCounts = $pets->groupBy('animal_type_id')->map->count();
        $occupancy = $this->overlappingOccupancy($activity, $requestedCounts->keys()->all(), $checkIn, $checkOut);

        $petSubtotals = [];
        foreach ($pets as $pet) {
            $petSubtotals[$pet->id] = '0.00';
        }

        $nights = 0;

        for ($date = $checkIn->copy(); $date->lt($checkOut); $date->addDay()) {
            $nights++;
            $dateString = $date->toDateString();
            $cycle = $this->resolveCycleForDate($cycles, $dateString);
            $weekday = $this->cycleService->weekDayForDate($dateString);

            if ($cycle === null || $this->cycleClosedOn($cycle, $weekday)) {
                throw ValidationException::withMessages([
                    'check_in_date' => ['The activity is not available for the selected dates.'],
                ]);
            }

            $settingsByType = $cycle->settings->keyBy('animal_type_id');

            foreach ($requestedCounts as $animalTypeId => $requestedCount) {
                $setting = $settingsByType->get($animalTypeId);

                if (! $setting instanceof ActivityCycleSetting) {
                    throw ValidationException::withMessages([
                        'pet_ids' => ['The activity does not accept this animal type.'],
                    ]);
                }

                $occupied = $this->occupiedOn($occupancy, (string) $animalTypeId, $dateString);

                if (($occupied + $requestedCount) > $setting->max_capacity) {
                    throw ValidationException::withMessages([
                        'pet_ids' => ['The activity does not have enough capacity for the selected dates.'],
                    ]);
                }
            }

            foreach ($pets as $pet) {
                $setting = $settingsByType->get($pet->animal_type_id);
                $price = $setting instanceof ActivityCycleSetting
                    ? $this->settingPriceForWeekday($setting, $weekday)
                    : null;

                if ($price === null) {
                    throw ValidationException::withMessages([
                        'check_in_date' => ['The activity is not available for the selected dates.'],
                    ]);
                }

                $petSubtotals[$pet->id] = bcadd($petSubtotals[$pet->id], $price, 2);
            }
        }

        $petPivots = [];
        $basePrice = '0.00';

        foreach ($pets as $pet) {
            $subtotal = $petSubtotals[$pet->id];
            $basePrice = bcadd($basePrice, $subtotal, 2);

            $petPivots[$pet->id] = [
                'price_per_night' => $nights > 0 ? bcdiv($subtotal, (string) $nights, 2) : '0.00',
                'number_of_nights' => $nights,
                'subtotal' => $subtotal,
            ];
        }

        $servicePivots = [];

        foreach ($services as $service) {
            $unitPrice = (string) $service->price;
            $basePrice = bcadd($basePrice, $unitPrice, 2);

            $servicePivots[$service->id] = [
                'quantity' => 1,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice,
            ];
        }

        $serviceFeeRate = (string) config('booking.user_service_fee_rate', '0.08');
        $hostCommissionRate = (string) config('booking.host_commission_rate', '0.06');

        $serviceFee = bcmul($basePrice, $serviceFeeRate, 2);
        $platformFee = bcmul($basePrice, $hostCommissionRate, 2);
        $totalPrice = bcadd($basePrice, $serviceFee, 2);
        $activityAmount = bcsub($basePrice, $platformFee, 2);

        return [$totalPrice, $serviceFee, $platformFee, $activityAmount, $petPivots, $servicePivots];
    }

    /**
     * @return Collection<int, ActivityCycle>
     */
    private function activeCyclesFor(Activity $activity): Collection
    {
        return ActivityCycle::with(['settings.prices', 'closedWeekDays'])
            ->where('activity_id', $activity->id)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();
    }

    /**
     * @param  Collection<int, ActivityCycle>  $cycles
     */
    private function resolveCycleForDate(Collection $cycles, string $date): ?ActivityCycle
    {
        return $cycles->first(
            fn (ActivityCycle $cycle): bool => ($cycle->start_date === null || $cycle->start_date->toDateString() <= $date)
                && ($cycle->end_date === null || $cycle->end_date->toDateString() >= $date)
        );
    }

    private function cycleClosedOn(ActivityCycle $cycle, WeekDayEnum $weekday): bool
    {
        $mask = (int) ($cycle->closedWeekDays->pluck('sum_weekdays')->first() ?? 0);

        return WeekDayEnum::contains($mask, $weekday);
    }

    private function settingPriceForWeekday(ActivityCycleSetting $setting, WeekDayEnum $weekday): ?string
    {
        $price = $setting->prices->first(
            fn (ActivityCycleSettingPrice $price): bool => $price->weekday === $weekday->value
        );

        return $price === null ? null : (string) $price->price;
    }

    /**
     * @param  array<int, mixed>  $animalTypeIds
     * @return Collection<int, \stdClass>
     */
    private function overlappingOccupancy(Activity $activity, array $animalTypeIds, Carbon $checkIn, Carbon $checkOut): Collection
    {
        if ($animalTypeIds === []) {
            return collect();
        }

        return DB::table('booking_pets')
            ->join('pets', 'booking_pets.pet_id', '=', 'pets.id')
            ->join('bookings', 'bookings.id', '=', 'booking_pets.booking_id')
            ->where('bookings.activity_id', $activity->id)
            ->whereIn('bookings.status', [BookingStatusEnum::CONFIRMED->value, BookingStatusEnum::IN_PROGRESS->value])
            ->where('bookings.check_in_date', '<', $checkOut->toDateString())
            ->where('bookings.check_out_date', '>', $checkIn->toDateString())
            ->whereIn('pets.animal_type_id', $animalTypeIds)
            ->get(['pets.animal_type_id', 'bookings.check_in_date', 'bookings.check_out_date']);
    }

    /**
     * @param  Collection<int, \stdClass>  $occupancy
     */
    private function occupiedOn(Collection $occupancy, string $animalTypeId, string $date): int
    {
        return $occupancy->filter(function (\stdClass $row) use ($animalTypeId, $date): bool {
            $rowCheckIn = substr((string) $row->check_in_date, 0, 10);
            $rowCheckOut = substr((string) $row->check_out_date, 0, 10);

            return (string) $row->animal_type_id === $animalTypeId
                && $rowCheckIn <= $date
                && $rowCheckOut > $date;
        })->count();
    }

    private function assertHostCanAcceptBookings(Activity $activity): void
    {
        $ready = $activity->resolveStripeAccountId() !== null
            && $activity->resolveChargesEnabled()
            && $activity->resolvePayoutsEnabled()
            && $activity->resolveOnboardingCompleted();

        if (! $ready) {
            throw ValidationException::withMessages([
                'activity_id' => ['This host cannot accept bookings yet. Their bank account is not connected.'],
            ]);
        }
    }

    private function validateAvailability(Activity $activity, Carbon $checkIn, Carbon $checkOut): void
    {
        $closedDays = $activity->availabilities()
            ->where('status', AvailabilityStatusEnum::CLOSED)
            ->whereBetween('date', [$checkIn->toDateString(), $checkOut->copy()->subDay()->toDateString()])
            ->exists();

        if ($closedDays) {
            throw ValidationException::withMessages([
                'check_in_date' => ['The activity is not available for the selected dates.'],
            ]);
        }
    }

    private function sendBookingReferenceIfConversationExists(Booking $booking, User $actor, ?string $message = null): void
    {
        $thread = BookingThread::where('booking_id', $booking->id)->with('conversation')->first();

        if ($thread?->conversation instanceof Conversation) {
            $this->conversationService->sendBookingReference($thread->conversation, $actor, $booking, $message);
        }
    }

    private function assertStatus(Booking $booking, array $allowedStatuses, string $action): void
    {
        if (! in_array($booking->status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot {$action} a booking with status {$booking->status->value}."],
            ]);
        }
    }

    private function assertCapacityForConfirm(Booking $booking): void
    {
        $booking->loadMissing(['activity', 'pets']);

        $activity = $booking->activity;

        if ($activity === null) {
            return;
        }

        $checkIn = Carbon::parse($booking->check_in_date->toDateString());
        $checkOut = Carbon::parse($booking->check_out_date->toDateString());

        $requestedCounts = $booking->pets->groupBy('animal_type_id')->map->count();
        $occupancy = $this->overlappingOccupancy($activity, $requestedCounts->keys()->all(), $checkIn, $checkOut);
        $cycles = $this->activeCyclesFor($activity);

        for ($date = $checkIn->copy(); $date->lt($checkOut); $date->addDay()) {
            $dateString = $date->toDateString();
            $cycle = $this->resolveCycleForDate($cycles, $dateString);

            if ($cycle === null) {
                continue;
            }

            $settingsByType = $cycle->settings->keyBy('animal_type_id');

            foreach ($requestedCounts as $animalTypeId => $requestedCount) {
                $setting = $settingsByType->get($animalTypeId);

                if (! $setting instanceof ActivityCycleSetting) {
                    continue;
                }

                $occupied = $this->occupiedOn($occupancy, (string) $animalTypeId, $dateString);

                if (($occupied + $requestedCount) > $setting->max_capacity) {
                    throw ValidationException::withMessages([
                        'status' => ['The activity no longer has enough capacity to confirm this booking.'],
                    ]);
                }
            }
        }
    }
}

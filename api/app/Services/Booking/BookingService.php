<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\AvailabilityStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingThread;
use App\Models\Conversation;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use App\Services\Activity\ActivityCycleService;
use App\Services\Conversation\ConversationService;
use App\Services\Stripe\StripeCustomerService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        private ActivityCycleService $cycleService
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

        if (! $activity->resolveChargesEnabled() || $activity->resolveStripeAccountId() === null) {
            throw ValidationException::withMessages([
                'activity_id' => ['This host cannot accept bookings yet. Their bank account is not connected.'],
            ]);
        }

        $checkIn = Carbon::parse($data['check_in_date']);
        $checkOut = Carbon::parse($data['check_out_date']);
        $nights = $checkIn->diffInDays($checkOut);

        $pets = Pet::whereIn('id', $data['pet_ids'])->get();
        $services = ! empty($data['service_ids'])
            ? Service::whereIn('id', $data['service_ids'])->get()
            : collect();

        [$booking, $totalPrice] = DB::transaction(function () use ($user, $data, $activity, $checkIn, $checkOut, $nights, $pets, $services): array {
            $cycle = $this->cycleService->resolveActiveCycle($activity, $checkIn->toDateString());

            $capacities = $cycle === null
                ? collect()
                : $cycle->settings->keyBy('animal_type_id');

            $this->validateAvailability($activity, $checkIn, $checkOut);
            $this->validateCapacity($activity, $pets, $capacities, $checkIn, $checkOut);

            [$totalPrice, $platformFee, $activityAmount, $petPivots, $servicePivots] =
                $this->calculatePrice($pets, $services, $capacities, (int) $nights);

            $booking = Booking::create([
                'user_id' => $user->id,
                'activity_id' => $activity->id,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'total_price' => $totalPrice,
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
            throw ValidationException::withMessages([
                'payment_method_id' => ['The payment could not be processed: '.$e->getMessage()],
            ]);
        }

        $booking->update([
            'stripe_payment_intent_id' => $pi->id,
            'stripe_charge_id' => $pi->latest_charge ?? null,
            'stripe_transfer_group' => 'booking_'.$booking->id,
            'payment_status' => $pi->status === 'succeeded' ? 'succeeded' : 'pending',
        ]);

        $booking->setAttribute('client_secret', $pi->client_secret);

        $this->conversationService->getOrCreateForBooking($user, $booking);

        return $booking->load([
            'activity.address',
            'activity.manager',
            'activity.collaborators',
            'pets',
            'services',
        ]);
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

    public function cancel(Booking $booking): Booking
    {
        $this->assertStatus($booking, [BookingStatusEnum::PENDING, BookingStatusEnum::CONFIRMED], 'cancel');

        $booking->update(['status' => BookingStatusEnum::CANCELLED]);

        return $booking->fresh();
    }

    public function confirm(Booking $booking, User $actor): Booking
    {
        $this->assertStatus($booking, [BookingStatusEnum::PENDING], 'confirm');

        $booking->update(['status' => BookingStatusEnum::CONFIRMED]);

        $accountId = $booking->activity->resolveStripeAccountId();

        if ($accountId && $booking->stripe_charge_id && (float) $booking->activity_amount > 0) {
            $amountToTransfer = (int) bcmul((string) $booking->activity_amount, '100', 0);
            $transfer = $this->stripe->transfers->create([
                'amount' => $amountToTransfer,
                'currency' => (string) config('services.stripe.currency', 'eur'),
                'destination' => $accountId,
                'source_transaction' => $booking->stripe_charge_id,
                'transfer_group' => $booking->stripe_transfer_group ?? ('booking_'.$booking->id),
                'metadata' => ['booking_id' => $booking->id],
            ]);
            $booking->update(['stripe_transfer_id' => $transfer->id]);
        }

        $this->sendBookingReferenceIfConversationExists($booking, $actor);

        return $booking->fresh();
    }

    public function complete(Booking $booking): Booking
    {
        $this->assertStatus($booking, [BookingStatusEnum::CONFIRMED, BookingStatusEnum::IN_PROGRESS], 'complete');

        $booking->update(['status' => BookingStatusEnum::COMPLETED]);

        return $booking->fresh();
    }

    public function refundOnReject(Booking $booking): Booking
    {
        if (! $booking->stripe_charge_id || $booking->payment_status !== 'succeeded') {
            return $booking;
        }
        $refund = $this->stripe->refunds->create([
            'charge' => $booking->stripe_charge_id,
            'metadata' => ['booking_id' => $booking->id],
        ]);
        $booking->update([
            'stripe_refund_id' => $refund->id,
            'refunded_amount' => bcdiv((string) $refund->amount, '100', 2),
            'refunded_at' => Carbon::now(),
            'payment_status' => 'refunded',
        ]);

        return $booking->fresh();
    }

    public function rejectByActivity(Booking $booking, User $actor): Booking
    {
        $this->assertStatus($booking, [BookingStatusEnum::PENDING], 'reject');

        $this->refundOnReject($booking);

        $booking->update(['status' => BookingStatusEnum::CANCELLED]);

        $this->sendBookingReferenceIfConversationExists($booking, $actor);

        return $booking->fresh();
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

    private function validateCapacity(
        Activity $activity,
        Collection $pets,
        Collection $capacities,
        Carbon $checkIn,
        Carbon $checkOut
    ): void {
        $animalTypeCounts = $pets->groupBy('animal_type_id')->map->count();

        $occupiedByType = Pet::select('pets.animal_type_id')
            ->selectRaw('COUNT(*) as occupied_count')
            ->join('booking_pets', 'booking_pets.pet_id', '=', 'pets.id')
            ->join('bookings', 'bookings.id', '=', 'booking_pets.booking_id')
            ->where('bookings.activity_id', $activity->id)
            ->whereIn('bookings.status', [BookingStatusEnum::CONFIRMED->value, BookingStatusEnum::IN_PROGRESS->value])
            ->where('bookings.check_in_date', '<', $checkOut->toDateString())
            ->where('bookings.check_out_date', '>', $checkIn->toDateString())
            ->whereIn('pets.animal_type_id', $animalTypeCounts->keys())
            ->groupBy('pets.animal_type_id')
            ->pluck('occupied_count', 'animal_type_id');

        foreach ($animalTypeCounts as $animalTypeId => $requestedCount) {
            if (! $capacities->has($animalTypeId)) {
                throw ValidationException::withMessages([
                    'pet_ids' => ['The activity does not accept this animal type.'],
                ]);
            }

            $occupied = (int) ($occupiedByType[$animalTypeId] ?? 0);
            $maxCapacity = $capacities->get($animalTypeId)->max_capacity;

            if (($occupied + $requestedCount) > $maxCapacity) {
                throw ValidationException::withMessages([
                    'pet_ids' => ['The activity does not have enough capacity for the selected dates.'],
                ]);
            }
        }
    }

    /**
     * @return array{string, string, string, array<string, array{price_per_night: string, number_of_nights: int, subtotal: string}>, array<string, array{quantity: int, unit_price: string, subtotal: string}>}
     */
    private function calculatePrice(
        Collection $pets,
        Collection $services,
        Collection $capacities,
        int $nights
    ): array {
        $petPivots = [];
        $totalPrice = '0.00';

        foreach ($pets as $pet) {
            $pricePerNight = (string) $capacities->get($pet->animal_type_id)->price;
            $subtotal = bcmul($pricePerNight, (string) $nights, 2);
            $totalPrice = bcadd($totalPrice, $subtotal, 2);

            $petPivots[$pet->id] = [
                'price_per_night' => $pricePerNight,
                'number_of_nights' => $nights,
                'subtotal' => $subtotal,
            ];
        }

        $servicePivots = [];

        foreach ($services as $service) {
            $unitPrice = (string) $service->price;
            $totalPrice = bcadd($totalPrice, $unitPrice, 2);

            $servicePivots[$service->id] = [
                'quantity' => 1,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice,
            ];
        }

        $platformFee = bcmul($totalPrice, '0.10', 2);
        $activityAmount = bcsub($totalPrice, $platformFee, 2);

        return [$totalPrice, $platformFee, $activityAmount, $petPivots, $servicePivots];
    }

    private function sendBookingReferenceIfConversationExists(Booking $booking, User $actor): void
    {
        $thread = BookingThread::where('booking_id', $booking->id)->with('conversation')->first();

        if ($thread?->conversation instanceof Conversation) {
            $this->conversationService->sendBookingReference($thread->conversation, $actor, $booking);
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
}

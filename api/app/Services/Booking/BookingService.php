<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\AvailabilityStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingThread;
use App\Models\Conversation;
use App\Models\Establishment;
use App\Models\EstablishmentCapacity;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class BookingService
{
    public function __construct(
        private ConversationService $conversationService,
        private StripeClient $stripe
    ) {}

    public function getUserBookings(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Booking::with(['establishment', 'pets', 'services'])
            ->where('user_id', $user->id)
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate($perPage);
    }

    public function getEstablishmentBookings(Establishment $establishment, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Booking::with(['user', 'pets', 'services'])
            ->where('establishment_id', $establishment->id)
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date_from']), fn ($q) => $q->where('check_in_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($q) => $q->where('check_out_date', '<=', $filters['date_to']))
            ->latest()
            ->paginate($perPage);
    }

    public function create(User $user, array $data): Booking
    {
        $establishment = Establishment::findOrFail($data['establishment_id']);
        $checkIn = Carbon::parse($data['check_in_date']);
        $checkOut = Carbon::parse($data['check_out_date']);
        $nights = $checkIn->diffInDays($checkOut);

        $pets = Pet::whereIn('id', $data['pet_ids'])->get();
        $services = ! empty($data['service_ids'])
            ? Service::whereIn('id', $data['service_ids'])->get()
            : collect();

        $booking = DB::transaction(function () use ($user, $data, $establishment, $checkIn, $checkOut, $nights, $pets, $services): Booking {
            $animalTypeIds = $pets->pluck('animal_type_id')->unique()->values();

            $capacities = EstablishmentCapacity::where('establishment_id', $establishment->id)
                ->whereIn('animal_type_id', $animalTypeIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('animal_type_id');

            $this->validateAvailability($establishment, $checkIn, $checkOut);
            $this->validateCapacity($establishment, $pets, $capacities, $checkIn, $checkOut);

            [$totalPrice, $platformFee, $establishmentAmount, $petPivots, $servicePivots] =
                $this->calculatePrice($pets, $services, $capacities, (int) $nights);

            $booking = Booking::create([
                'user_id' => $user->id,
                'establishment_id' => $establishment->id,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'total_price' => $totalPrice,
                'platform_fee' => $platformFee,
                'establishment_amount' => $establishmentAmount,
                'status' => BookingStatus::PENDING,
                'special_requests' => $data['special_requests'] ?? null,
            ]);

            $booking->pets()->attach($petPivots);

            if ($services->isNotEmpty()) {
                $booking->services()->attach($servicePivots);
            }

            $session = $this->createCheckoutSession($booking, $totalPrice, $user, $establishment);

            $booking->update([
                'stripe_payment_intent_id' => is_string($session->payment_intent)
                    ? $session->payment_intent
                    : ($session->payment_intent->id ?? null),
                'payment_status' => 'pending',
            ]);

            $booking->setAttribute('checkout_url', $session->url);

            return $booking->load([
                'establishment.address',
                'establishment.manager',
                'establishment.collaborators',
                'pets',
                'services',
            ]);
        });

        $this->conversationService->getOrCreateForBooking($user, $booking);

        return $booking;
    }

    private function createCheckoutSession(
        Booking $booking,
        string $totalPrice,
        User $user,
        Establishment $establishment
    ): Session {
        $amountInCents = (int) bcmul($totalPrice, '100', 0);
        $currency = (string) config('services.stripe.currency', 'eur');
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        $paymentIntentData = [
            'metadata' => [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'establishment_id' => $establishment->id,
            ],
        ];

        if ($establishment->stripe_account_id && $establishment->stripe_charges_enabled) {
            $platformFeeInCents = (int) bcmul((string) $booking->platform_fee, '100', 0);
            $paymentIntentData['transfer_data'] = ['destination' => $establishment->stripe_account_id];
            $paymentIntentData['application_fee_amount'] = $platformFeeInCents;
        }

        return $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $establishment->name,
                        'description' => "Booking {$booking->check_in_date->toDateString()} → {$booking->check_out_date->toDateString()}",
                    ],
                    'unit_amount' => $amountInCents,
                ],
                'quantity' => 1,
            ]],
            'success_url' => $frontendUrl.'/en/explore?booking_success=1',
            'cancel_url' => $frontendUrl."/en/host/{$establishment->id}/book?check_in={$booking->check_in_date->toDateString()}&check_out={$booking->check_out_date->toDateString()}",
            'customer_email' => $user->email,
            'payment_intent_data' => $paymentIntentData,
            'metadata' => [
                'booking_id' => $booking->id,
            ],
        ]);
    }

    public function cancel(Booking $booking): Booking
    {
        $this->assertStatus($booking, [BookingStatus::PENDING, BookingStatus::CONFIRMED], 'cancel');

        $booking->update(['status' => BookingStatus::CANCELLED]);

        return $booking->fresh();
    }

    public function confirm(Booking $booking, User $actor): Booking
    {
        $this->assertStatus($booking, [BookingStatus::PENDING], 'confirm');

        $booking->update(['status' => BookingStatus::CONFIRMED]);

        $this->sendBookingReferenceIfConversationExists($booking, $actor);

        return $booking->fresh();
    }

    public function complete(Booking $booking): Booking
    {
        $this->assertStatus($booking, [BookingStatus::CONFIRMED, BookingStatus::IN_PROGRESS], 'complete');

        $booking->update(['status' => BookingStatus::COMPLETED]);

        return $booking->fresh();
    }

    public function rejectByEstablishment(Booking $booking, User $actor): Booking
    {
        $this->assertStatus($booking, [BookingStatus::PENDING], 'reject');

        $booking->update(['status' => BookingStatus::CANCELLED]);

        $this->sendBookingReferenceIfConversationExists($booking, $actor);

        return $booking->fresh();
    }

    private function validateAvailability(Establishment $establishment, Carbon $checkIn, Carbon $checkOut): void
    {
        $closedDays = $establishment->availabilities()
            ->where('status', AvailabilityStatus::CLOSED)
            ->whereBetween('date', [$checkIn->toDateString(), $checkOut->copy()->subDay()->toDateString()])
            ->exists();

        if ($closedDays) {
            throw ValidationException::withMessages([
                'check_in_date' => ['The establishment is not available for the selected dates.'],
            ]);
        }
    }

    private function validateCapacity(
        Establishment $establishment,
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
            ->where('bookings.establishment_id', $establishment->id)
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED->value, BookingStatus::IN_PROGRESS->value])
            ->where('bookings.check_in_date', '<', $checkOut->toDateString())
            ->where('bookings.check_out_date', '>', $checkIn->toDateString())
            ->whereIn('pets.animal_type_id', $animalTypeCounts->keys())
            ->groupBy('pets.animal_type_id')
            ->pluck('occupied_count', 'animal_type_id');

        foreach ($animalTypeCounts as $animalTypeId => $requestedCount) {
            if (! $capacities->has($animalTypeId)) {
                throw ValidationException::withMessages([
                    'pet_ids' => ['The establishment does not accept this animal type.'],
                ]);
            }

            $occupied = (int) ($occupiedByType[$animalTypeId] ?? 0);
            $maxCapacity = $capacities->get($animalTypeId)->max_capacity;

            if (($occupied + $requestedCount) > $maxCapacity) {
                throw ValidationException::withMessages([
                    'pet_ids' => ['The establishment does not have enough capacity for the selected dates.'],
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
            $pricePerNight = (string) $capacities->get($pet->animal_type_id)->price_per_night;
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
        $establishmentAmount = bcsub($totalPrice, $platformFee, 2);

        return [$totalPrice, $platformFee, $establishmentAmount, $petPivots, $servicePivots];
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

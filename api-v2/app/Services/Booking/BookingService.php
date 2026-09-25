<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingItemStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\FinancialOperationTypeEnum;
use App\Enums\PaymentKindEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ResourceBookingKindEnum;
use App\Events\Booking\BookingConfirmed;
use App\Events\Booking\BookingCreated;
use App\Events\Booking\BookingRejected;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\AgendaResource;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\Pet;
use App\Models\ResourceBooking;
use App\Models\Service;
use App\Models\User;
use App\Services\Agenda\AgendaService;
use App\Services\Finance\FinancialJournalService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Réservation d'un séjour ou d'un rendez-vous : création, réponse du pro, et règle unique des changements de statut.
 */
class BookingService
{
    /**
     * Relations affichées avec une réservation.
     */
    public const array RELATIONS = [
        'activity.media',
        'activity.profession',
        'organization',
        'user',
        'serviceAddress',
        'units.unitType',
        'pets.animalType',
        'items.service',
        'items.pet',
        'items.resourceBooking.resource',
        'payments.refunds',
        'payout',
    ];

    public function __construct(
        private readonly QuoteService $quotes,
        private readonly BookingPaymentService $payments,
        private readonly FinancialJournalService $journal,
        private readonly AgendaService $agenda,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function forClient(User $client, array $filters = []): LengthAwarePaginator
    {
        return $client->bookings()
            ->with(self::RELATIONS)
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function forActivity(Activity $activity, array $filters = []): LengthAwarePaginator
    {
        return $activity->bookings()
            ->with(self::RELATIONS)
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['from']), fn ($query) => $query->whereDate('end_date', '>=', $filters['from']))
            ->when(isset($filters['to']), fn ($query) => $query->whereDate('start_date', '<=', $filters['to']))
            ->orderBy('start_date')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? null);
    }

    /**
     * Le paiement est autorisé avant d'écrire quoi que ce soit : une carte refusée ne laisse aucune réservation.
     * La réservation est ensuite enregistrée sous verrou des places ou de la ressource demandées, après un nouveau
     * devis qui contrôle la capacité nuit par nuit, ou le créneau ; si elle échoue, l'autorisation est libérée.
     *
     * @param  array<string, mixed>  $data  demande validée (StoreBookingRequest)
     */
    public function create(User $client, array $data): Booking
    {
        $quote = $this->quotes->quote($client, $data);

        // Le rendez-vous garde la ressource de son devis : c'est elle qui est verrouillée puis revérifiée.
        if ($quote->appointment !== null) {
            $data['resource_id'] = $quote->appointment['resource']->id;
        }

        $bookingId = (string) Str::uuid();
        $intent = $this->payments->authorize($client, $quote, $bookingId, (string) $data['payment_method_id']);

        try {
            $booking = DB::transaction(function () use ($client, $data, $quote, $bookingId, $intent): Booking {
                $this->lockCapacity($quote);
                $locked = $this->quotes->quote($client, $data);

                if (bccomp($locked->totalPrice, $quote->totalPrice, 2) !== 0) {
                    throw ValidationException::withMessages(['activity_id' => __('booking.price_changed')]);
                }

                $booking = $this->store($client, $locked, $bookingId, $data);
                $payment = $this->payments->recordInitial($booking, $intent);
                $booking->items()->update(['booking_payment_id' => $payment->id]);
                $booking->forceFill(['payment_status' => $payment->status])->save();

                return $booking;
            });
        } catch (Throwable $exception) {
            $this->cancelAuthorization($intent->id);

            throw $exception;
        }

        if ($booking->payment_status === PaymentStatusEnum::REQUIRES_CAPTURE) {
            BookingCreated::dispatch($booking);
        }

        // Le client confirme le paiement dans le front (3-D Secure) ; la demande part au pro ensuite (webhook).
        if ($booking->payment_status === PaymentStatusEnum::REQUIRES_ACTION) {
            $booking->setAttribute('client_secret', $intent->client_secret);
        }

        return $booking->load(self::RELATIONS);
    }

    /**
     * Le pro accepte : le paiement initial est capturé.
     */
    public function confirm(Booking $booking): Booking
    {
        $captured = DB::transaction(function () use ($booking): bool {
            $booking = $this->lock($booking);
            $this->assertCanTransition($booking, BookingStatusEnum::CONFIRMED);
            $payment = $this->initialPayment($booking);

            if ($payment->status !== PaymentStatusEnum::REQUIRES_CAPTURE) {
                throw ValidationException::withMessages(['payment' => __('booking.payment_not_authorized')]);
            }

            if (! $this->payments->capture($booking, $payment)) {
                $booking->forceFill(['payment_status' => PaymentStatusEnum::FAILED])->save();

                return false;
            }

            $booking->forceFill(['payment_status' => PaymentStatusEnum::SUCCEEDED]);
            $this->transition($booking, BookingStatusEnum::CONFIRMED);

            return true;
        });

        if (! $captured) {
            throw ValidationException::withMessages(['payment' => __('booking.capture_failed')]);
        }

        BookingConfirmed::dispatch($booking->refresh());

        return $booking->load(self::RELATIONS);
    }

    /**
     * Le pro refuse : l'autorisation est libérée, le client ne paie rien.
     */
    public function reject(Booking $booking): Booking
    {
        DB::transaction(function () use ($booking): void {
            $booking = $this->lock($booking);
            $this->assertCanTransition($booking, BookingStatusEnum::REJECTED);
            $this->releaseInitial($booking);
            $this->transition($booking, BookingStatusEnum::REJECTED);
        });

        BookingRejected::dispatch($booking->refresh());

        return $booking->load(self::RELATIONS);
    }

    /**
     * Libère l'autorisation du paiement initial d'une demande qui ne sera pas acceptée.
     */
    public function releaseInitial(Booking $booking): void
    {
        $payment = $this->initialPayment($booking);

        if (in_array($payment->status, [PaymentStatusEnum::REQUIRES_CAPTURE, PaymentStatusEnum::REQUIRES_ACTION, PaymentStatusEnum::PENDING], true)) {
            $this->payments->release($booking, $payment);
        }

        $booking->forceFill(['payment_status' => $payment->status])->save();
        $this->cancelItems($booking->items());
    }

    /**
     * Retire des prestations : elles passent à « annulée » et libèrent leur place dans l'agenda. Celles déjà
     * réalisées ne bougent pas.
     *
     * @param  Builder<BookingItem>|HasMany<BookingItem, Booking>|HasMany<BookingItem, BookingPayment>  $items
     */
    public function cancelItems(Builder|HasMany $items): void
    {
        $ids = $items->whereNot('status', BookingItemStatusEnum::DONE)->pluck('id');

        ResourceBooking::query()->whereIn('booking_item_id', $ids)->delete();
        BookingItem::query()->whereKey($ids)->update(['status' => BookingItemStatusEnum::CANCELLED]);
    }

    /**
     * Enregistre une option de séjour, à placer. Une prestation qui se place dans l'agenda donne une ligne par
     * passage : trois promenades font trois lignes, placées chacune à son heure.
     *
     * @param  array{service: Service, pet: Pet, quantity: int, unit_price: numeric-string, duration_minutes: int|null}  $option
     * @return list<BookingItem>
     */
    public function addOptionItems(Booking $booking, array $option): array
    {
        $passes = $option['service']->requires_scheduling ? $option['quantity'] : 1;
        $quantity = intdiv($option['quantity'], $passes);

        return array_map(fn (): BookingItem => $booking->items()->create([
            'service_id' => $option['service']->id,
            'pet_id' => $option['pet']->id,
            'status' => BookingItemStatusEnum::TO_SCHEDULE,
            'quantity' => $quantity,
            'unit_price' => $option['unit_price'],
            'subtotal' => Money::multiply($option['unit_price'], (string) $quantity),
            'duration_minutes' => $option['duration_minutes'],
        ]), range(1, $passes));
    }

    /**
     * Seule porte d'entrée d'un changement de statut : la transition doit être permise, et elle est journalisée.
     *
     * @param  array<string, mixed>  $attributes  autres colonnes écrites avec le statut
     */
    public function transition(Booking $booking, BookingStatusEnum $status, array $attributes = []): void
    {
        $this->assertCanTransition($booking, $status);

        $booking->forceFill(['status' => $status, ...$attributes])->save();
        $this->journal->record(FinancialOperationTypeEnum::STATUS_CHANGE, $booking, metadata: ['status' => $status->value]);
    }

    public function lock(Booking $booking): Booking
    {
        $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
        $booking->setRawAttributes($locked->getAttributes(), true);

        return $booking;
    }

    public function initialPayment(Booking $booking): BookingPayment
    {
        return $booking->payments()->where('kind', PaymentKindEnum::INITIAL)->firstOrFail();
    }

    /**
     * Deux demandes simultanées pour les mêmes places, ou pour la même ressource, passent l'une après l'autre.
     */
    private function lockCapacity(BookingQuote $quote): void
    {
        if ($quote->appointment !== null) {
            $this->agenda->lock($quote->appointment['resource']);

            return;
        }

        ActivityUnitType::query()->whereKey($quote->unitTypeIds())->lockForUpdate()->get();
    }

    private function assertCanTransition(Booking $booking, BookingStatusEnum $status): void
    {
        if (! $booking->status->canTransitionTo($status)) {
            throw ValidationException::withMessages(['status' => __('booking.invalid_transition', [
                'from' => __('booking.statuses.'.$booking->status->value),
                'to' => __('booking.statuses.'.$status->value),
            ])]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function store(User $client, BookingQuote $quote, string $bookingId, array $data): Booking
    {
        $serviceAddress = $quote->serviceAddress?->replicate();
        $serviceAddress?->save();

        $booking = new Booking([
            'organization_id' => $quote->activity->organization_id,
            'activity_id' => $quote->activity->id,
            'user_id' => $client->id,
            'start_date' => $quote->startDate->toDateString(),
            'end_date' => $quote->endDate->toDateString(),
            'location_mode' => $quote->location,
            'service_address_id' => $serviceAddress?->id,
            'special_requests' => $data['special_requests'] ?? null,
            'currency' => $quote->currency,
            'total_price' => $quote->totalPrice,
            'service_fee' => $quote->serviceFee,
            'platform_fee' => $quote->platformFee,
            'activity_amount' => $quote->activityAmount,
            'travel_fee' => $quote->travelFee,
            'vat_rate' => $quote->vatRate,
            'cancellation_policy' => $quote->cancellationPolicy,
        ]);
        $booking->id = $bookingId;
        $booking->forceFill([
            'status' => BookingStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PENDING,
            'stripe_transfer_group' => 'booking_'.$bookingId,
        ])->save();

        foreach ($quote->units as $unit) {
            $bookingUnit = $booking->units()->create([
                'activity_unit_type_id' => $unit['unit_type']->id,
                'quantity' => 1,
                'nights' => $unit['nights'],
                'subtotal' => $unit['subtotal'],
                'price_breakdown' => $unit['breakdown'],
            ]);

            foreach ($unit['pets'] as $pet) {
                $booking->pets()->attach($pet->id, ['booking_unit_id' => $bookingUnit->id]);
            }
        }

        foreach ($quote->options as $option) {
            $this->addOptionItems($booking, $option);
        }

        if ($quote->appointment !== null) {
            $this->storeAppointment($booking, $quote->appointment);
        }

        return $booking;
    }

    /**
     * Une ligne par animal, chacune à son horaire et occupant la ressource.
     *
     * @param  array{resource: AgendaResource, lines: list<array{service: Service, pet: Pet, unit_price: numeric-string, subtotal: numeric-string, duration_minutes: int, starts_at: CarbonImmutable, ends_at: CarbonImmutable}>}  $appointment
     */
    private function storeAppointment(Booking $booking, array $appointment): void
    {
        foreach ($appointment['lines'] as $line) {
            $booking->pets()->attach($line['pet']->id);

            $item = $booking->items()->create([
                'service_id' => $line['service']->id,
                'pet_id' => $line['pet']->id,
                'status' => BookingItemStatusEnum::SCHEDULED,
                'quantity' => 1,
                'unit_price' => $line['unit_price'],
                'subtotal' => $line['subtotal'],
                'duration_minutes' => $line['duration_minutes'],
                'starts_at' => $line['starts_at']->utc(),
                'ends_at' => $line['ends_at']->utc(),
            ]);

            $this->agenda->occupy($appointment['resource'], $line['starts_at'], $line['ends_at'], ResourceBookingKindEnum::BOOKING, [
                'booking_item_id' => $item->id,
                'created_by' => $booking->user_id,
            ]);
        }
    }

    private function cancelAuthorization(string $paymentIntentId): void
    {
        try {
            $this->payments->cancelIntent($paymentIntentId);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}

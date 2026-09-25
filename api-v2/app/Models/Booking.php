<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingModeEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\CancellationPolicyEnum;
use App\Enums\CancelledByRoleEnum;
use App\Enums\LocationModeEnum;
use App\Enums\PaymentStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Réservation d'un client auprès d'une activité. L'entreprise, la politique d'annulation, le taux de TVA,
 * le lieu, l'adresse et les frais de déplacement sont figés à la création.
 *
 * Les montants sont les montants nets en cours : ils suivent les compléments et les remboursements.
 * Un paiement, lui, ne change jamais (booking_payments) ; ses remboursements sont des lignes à part.
 * total_price = montant des prestations + service_fee (frais Kennelo payés par le client) ;
 * activity_amount = montant des prestations - platform_fee (commission) : ce qui est versé à l'entreprise.
 *
 * Le statut, le paiement et l'annulation ne sont jamais remplis depuis une requête : les services les
 * écrivent avec forceFill(), en respectant BookingStatusEnum::canTransitionTo().
 *
 * @property string $id
 * @property string $organization_id
 * @property string $activity_id
 * @property string $user_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property LocationModeEnum $location_mode
 * @property string|null $service_address_id
 * @property BookingStatusEnum $status
 * @property string|null $special_requests
 * @property string $currency
 * @property numeric-string $total_price
 * @property numeric-string $service_fee
 * @property numeric-string $platform_fee
 * @property numeric-string $activity_amount
 * @property numeric-string $travel_fee
 * @property numeric-string $vat_rate
 * @property PaymentStatusEnum $payment_status
 * @property string|null $stripe_transfer_group
 * @property CancellationPolicyEnum $cancellation_policy
 * @property Carbon|null $cancelled_at
 * @property string|null $cancelled_by
 * @property CancelledByRoleEnum|null $cancelled_by_role
 * @property Carbon|null $reminded_at
 * @property Carbon|null $created_at
 * @property-read Organization|null $organization
 * @property-read Activity|null $activity
 * @property-read User|null $user
 * @property-read Address|null $serviceAddress
 * @property-read BookingPayout|null $payout
 */
class Booking extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'activity_id',
        'user_id',
        'start_date',
        'end_date',
        'location_mode',
        'service_address_id',
        'special_requests',
        'currency',
        'total_price',
        'service_fee',
        'platform_fee',
        'activity_amount',
        'travel_fee',
        'vat_rate',
        'cancellation_policy',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'location_mode' => LocationModeEnum::class,
            'status' => BookingStatusEnum::class,
            'total_price' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'activity_amount' => 'decimal:2',
            'travel_fee' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'payment_status' => PaymentStatusEnum::class,
            'cancellation_policy' => CancellationPolicyEnum::class,
            'cancelled_at' => 'datetime',
            'cancelled_by_role' => CancelledByRoleEnum::class,
            'reminded_at' => 'datetime',
        ];
    }

    /**
     * Un rendez-vous occupe des créneaux de l'agenda ; un séjour, des places sur des dates. Le mode du métier ne
     * change plus une fois qu'il a des activités (ProfessionService).
     */
    public function isAppointment(): bool
    {
        return $this->loadMissing('activity.profession')->activity?->profession?->booking_mode === BookingModeEnum::APPOINTMENT;
    }

    /**
     * Début de la réservation, dans le fuseau de l'activité : le premier créneau d'un rendez-vous, minuit le jour
     * de l'arrivée pour un séjour. La politique d'annulation se compte à partir de là.
     */
    public function startsAt(): CarbonImmutable
    {
        $first = $this->isAppointment() ? $this->loadMissing('items')->items->min('starts_at') : null;

        return $first !== null
            ? CarbonImmutable::instance($first)->setTimezone($this->timezone())
            : CarbonImmutable::parse($this->start_date->toDateString(), $this->timezone());
    }

    /**
     * Fin de la réservation : la fin du dernier créneau d'un rendez-vous, minuit après le jour du départ pour un séjour.
     * Un rendez-vous sans ligne planifiée, qui ne devrait pas exister, se lit comme un séjour, par ses dates.
     */
    public function endsAt(): CarbonImmutable
    {
        $last = $this->isAppointment() ? $this->loadMissing('items')->items->max('ends_at') : null;

        return $last !== null
            ? CarbonImmutable::instance($last)->setTimezone($this->timezone())
            : CarbonImmutable::parse($this->end_date->toDateString(), $this->timezone())->addDay();
    }

    private function timezone(): string
    {
        return $this->activity->timezone ?? (string) config('activities.default_timezone');
    }

    /**
     * Montant des prestations, hors frais Kennelo payés par le client.
     *
     * @return numeric-string
     */
    public function itemsAmount(): string
    {
        return bcsub($this->total_price, $this->service_fee, 2);
    }

    /**
     * Réservations qui occupent des places de l'activité sur la période.
     */
    public function scopeOccupying(Builder $query): Builder
    {
        return $query->whereIn('bookings.status', BookingStatusEnum::occupying());
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class)->withTrashed();
    }

    /**
     * L'historique reste lisible après la suppression logique de l'activité.
     *
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Copie de l'adresse du client pour un séjour chez lui, jamais modifiée.
     *
     * @return BelongsTo<Address, $this>
     */
    public function serviceAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'service_address_id');
    }

    /**
     * @return HasMany<BookingUnit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(BookingUnit::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Pet, $this, BookingPet, 'placement'>
     */
    public function pets(): BelongsToMany
    {
        return $this->belongsToMany(Pet::class, 'booking_pets')
            ->using(BookingPet::class)
            ->as('placement')
            ->withPivot('booking_unit_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<BookingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return HasMany<BookingPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BookingPayment::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return HasManyThrough<BookingRefund, BookingPayment, $this>
     */
    public function refunds(): HasManyThrough
    {
        return $this->hasManyThrough(BookingRefund::class, BookingPayment::class);
    }

    /**
     * @return HasOne<BookingPayout, $this>
     */
    public function payout(): HasOne
    {
        return $this->hasOne(BookingPayout::class);
    }

    /**
     * @return HasMany<FinancialOperation, $this>
     */
    public function operations(): HasMany
    {
        return $this->hasMany(FinancialOperation::class)->orderBy('created_at')->orderBy('id');
    }
}

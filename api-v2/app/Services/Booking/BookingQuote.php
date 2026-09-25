<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\CancellationPolicyEnum;
use App\Enums\LocationModeEnum;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\Address;
use App\Models\AgendaResource;
use App\Models\Pet;
use App\Models\Service;
use Carbon\CarbonImmutable;

/**
 * Devis d'un séjour ou d'un rendez-vous : ce que le client paiera et ce que l'entreprise recevra. Produit par
 * QuoteService, il sert à l'affichage (/bookings/quote) comme à la création, qui garantit ainsi le même prix.
 *
 * Un séjour a des places (units) et des options ; un rendez-vous (appointment) a une ligne par animal, à la suite
 * sur la même ressource, et se tient le jour local de son début.
 *
 * items_amount = places + options + lignes du rendez-vous + frais de déplacement
 * total_price = items_amount + service_fee (frais Kennelo payés par le client)
 * activity_amount = items_amount - platform_fee (commission prélevée sur l'entreprise)
 */
final readonly class BookingQuote
{
    /**
     * @param  list<array{unit_type: ActivityUnitType, pets: list<Pet>, nights: int, subtotal: numeric-string, breakdown: list<array{date: string, pricing_period_id: string, price: numeric-string, extra_animals_price: numeric-string}>}>  $units
     * @param  list<array{service: Service, pet: Pet, quantity: int, unit_price: numeric-string, subtotal: numeric-string, duration_minutes: int|null, is_included: bool}>  $options
     * @param  array{resource: AgendaResource, starts_at: CarbonImmutable, ends_at: CarbonImmutable, lines: list<array{service: Service, pet: Pet, unit_price: numeric-string, subtotal: numeric-string, duration_minutes: int, starts_at: CarbonImmutable, ends_at: CarbonImmutable}>}|null  $appointment
     * @param  numeric-string  $travelFee
     * @param  numeric-string  $itemsAmount
     * @param  numeric-string  $serviceFee
     * @param  numeric-string  $platformFee
     * @param  numeric-string  $totalPrice
     * @param  numeric-string  $activityAmount
     * @param  numeric-string  $vatRate
     */
    public function __construct(
        public Activity $activity,
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
        public int $nights,
        public LocationModeEnum $location,
        public ?Address $serviceAddress,
        public array $units,
        public array $options,
        public ?array $appointment,
        public string $travelFee,
        public string $itemsAmount,
        public string $serviceFee,
        public string $platformFee,
        public string $totalPrice,
        public string $activityAmount,
        public string $vatRate,
        public CancellationPolicyEnum $cancellationPolicy,
        public string $currency,
    ) {}

    /**
     * @return list<string>
     */
    public function unitTypeIds(): array
    {
        return array_values(array_unique(array_map(fn (array $unit): string => $unit['unit_type']->id, $this->units)));
    }
}

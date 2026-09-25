<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingModeEnum;
use App\Enums\LocationModeEnum;
use App\Enums\ServiceOfferEnum;
use App\Enums\VatRegimeEnum;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\Address;
use App\Models\AgendaResource;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use App\Services\Agenda\Exceptions\SlotUnavailableException;
use App\Services\Catalog\ServicePriceResolver;
use App\Services\Pricing\ActivityPricingService;
use App\Services\Pricing\Exceptions\StayUnavailableException;
use App\Services\Pricing\StayPriceCalculator;
use App\Services\Stay\UnitTypeService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Calcule le devis d'un séjour ou d'un rendez-vous. La création d'une réservation le recalcule sous verrou, avec les
 * mêmes règles.
 *
 * Les frais Kennelo (réglage user_service_fee_rate) s'ajoutent au prix payé par le client ; la commission de
 * l'offre de l'entreprise se retire de ce qui lui est versé. Frais de déplacement : aucun barème n'existe
 * encore, ils valent 0.
 */
class QuoteService
{
    public function __construct(
        private readonly ActivityPricingService $pricing,
        private readonly UnitTypeService $unitTypes,
        private readonly ServicePriceResolver $prices,
        private readonly AppointmentService $appointments,
    ) {}

    /**
     * @param  array<string, mixed>  $data  demande validée (StoreBookingRequest)
     *
     * @throws ValidationException|StayUnavailableException|SlotUnavailableException
     */
    public function quote(User $client, array $data): BookingQuote
    {
        $activity = $this->bookableActivity((string) $data['activity_id']);
        $pets = $client->pets()->with(['animalType', 'animalBreed'])->get()->keyBy('id');

        return $activity->profession?->booking_mode === BookingModeEnum::APPOINTMENT
            ? $this->appointment($client, $activity, $data, $pets)
            : $this->stay($client, $activity, $data, $pets);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<string, Pet>  $pets
     */
    private function stay(User $client, Activity $activity, array $data, Collection $pets): BookingQuote
    {
        $start = CarbonImmutable::parse((string) $data['start_date']);
        $end = CarbonImmutable::parse((string) $data['end_date']);
        $dates = $activity->profession->billing_unit->stayDates($start, $end);

        if ($dates === [] || count($dates) > (int) config('booking.max_stay_days')) {
            throw ValidationException::withMessages(['end_date' => __('booking.invalid_length', ['max' => config('booking.max_stay_days')])]);
        }

        $calculator = $this->pricing->calculator($activity, $start, $end);

        if (count($dates) < $minStay = $calculator->minStay($dates[0])) {
            throw StayUnavailableException::belowMinStay($minStay);
        }

        $units = $this->priceUnits($activity, $data['units'], $pets, $calculator, $dates);
        $this->assertCapacity($activity, $units, $dates);
        $options = $this->priceOptions($activity, $data['options'] ?? [], $pets);

        return $this->build($client, $activity, $data, $start, $end, count($dates), units: $units, options: $options);
    }

    /**
     * Le rendez-vous se tient le jour local de son début : start_date et end_date valent ce jour.
     *
     * @param  array<string, mixed>  $data
     * @param  Collection<string, Pet>  $pets
     */
    private function appointment(User $client, Activity $activity, array $data, Collection $pets): BookingQuote
    {
        $appointment = $this->appointments->plan(
            $activity,
            (string) $data['service_id'],
            array_map(fn (string $petId): Pet => $pets[$petId], $data['pet_ids']),
            CarbonImmutable::parse((string) $data['starts_at']),
            $data['resource_id'] ?? null,
        );
        $day = $appointment['starts_at']->setTimezone($activity->timezone)->startOfDay();

        return $this->build($client, $activity, $data, $day, $day, 0, appointment: $appointment);
    }

    /**
     * Ajoute le lieu et les frais aux prestations chiffrées.
     *
     * @param  array<string, mixed>  $data
     * @param  list<array{unit_type: ActivityUnitType, pets: list<Pet>, nights: int, subtotal: numeric-string, breakdown: list<array{date: string, pricing_period_id: string, price: numeric-string, extra_animals_price: numeric-string}>}>  $units
     * @param  list<array{service: Service, pet: Pet, quantity: int, unit_price: numeric-string, subtotal: numeric-string, duration_minutes: int|null, is_included: bool}>  $options
     * @param  array{resource: AgendaResource, starts_at: CarbonImmutable, ends_at: CarbonImmutable, lines: list<array{service: Service, pet: Pet, unit_price: numeric-string, subtotal: numeric-string, duration_minutes: int, starts_at: CarbonImmutable, ends_at: CarbonImmutable}>}|null  $appointment
     */
    private function build(User $client, Activity $activity, array $data, CarbonImmutable $start, CarbonImmutable $end, int $nights, array $units = [], array $options = [], ?array $appointment = null): BookingQuote
    {
        [$location, $serviceAddress] = $this->location($activity, $client, $data, allowRemote: $appointment !== null);

        $travelFee = '0.00';
        $itemsAmount = Money::sum(
            $travelFee,
            ...array_column($units, 'subtotal'),
            ...array_column($options, 'subtotal'),
            ...array_column($appointment['lines'] ?? [], 'subtotal'),
        );
        $organization = $activity->organization()->firstOrFail();
        $serviceFee = Money::multiply($itemsAmount, (string) setting('user_service_fee_rate'));
        $platformFee = Money::multiply($itemsAmount, $organization->effectivePlan()->commissionRate());

        return new BookingQuote(
            activity: $activity,
            startDate: $start,
            endDate: $end,
            nights: $nights,
            location: $location,
            serviceAddress: $serviceAddress,
            units: $units,
            options: $options,
            appointment: $appointment,
            travelFee: $travelFee,
            itemsAmount: $itemsAmount,
            serviceFee: $serviceFee,
            platformFee: $platformFee,
            totalPrice: Money::sum($itemsAmount, $serviceFee),
            activityAmount: bcsub($itemsAmount, $platformFee, 2),
            vatRate: $organization->vat_regime === VatRegimeEnum::FRANCHISE ? '0.00' : Money::round((string) config('booking.vat_rate')),
            cancellationPolicy: $activity->cancellation_policy,
            currency: mb_strtoupper((string) setting('currency')),
        );
    }

    private function bookableActivity(string $activityId): Activity
    {
        $activity = Activity::query()->bookable()->with('profession')->find($activityId);

        if ($activity === null) {
            throw ValidationException::withMessages(['activity_id' => __('booking.activity_unavailable')]);
        }

        return $activity;
    }

    /**
     * Une ligne par place occupée, avec les animaux qui la partagent.
     *
     * @param  list<array{unit_type_id: string, pet_ids: list<string>}>  $requested
     * @param  Collection<string, Pet>  $pets
     * @param  list<CarbonImmutable>  $dates
     * @return list<array{unit_type: ActivityUnitType, pets: list<Pet>, nights: int, subtotal: numeric-string, breakdown: list<array{date: string, pricing_period_id: string, price: numeric-string, extra_animals_price: numeric-string}>}>
     */
    private function priceUnits(Activity $activity, array $requested, Collection $pets, StayPriceCalculator $calculator, array $dates): array
    {
        $unitTypes = $this->unitTypes->forActivity($activity)->keyBy('id');
        $units = [];

        foreach ($requested as $index => $unit) {
            $unitType = $unitTypes->get($unit['unit_type_id']);
            $unitPets = array_map(fn (string $petId): Pet => $pets[$petId], $unit['pet_ids']);

            if ($unitType === null) {
                throw ValidationException::withMessages(["units.{$index}.unit_type_id" => __('validation.exists', ['attribute' => 'unit_type_id'])]);
            }

            if (count($unitPets) > $unitType->max_animals_per_unit) {
                throw ValidationException::withMessages(["units.{$index}.pet_ids" => __('booking.too_many_animals', ['max' => $unitType->max_animals_per_unit])]);
            }

            $accepted = $unitType->animalTypes->modelKeys();

            foreach ($unitPets as $pet) {
                if (! in_array($pet->animal_type_id, $accepted, true)) {
                    throw ValidationException::withMessages(["units.{$index}.pet_ids" => __('booking.species_not_accepted', ['pet' => $pet->name, 'unit' => $unitType->name])]);
                }
            }

            $price = $calculator->priceUnit($unitType->id, count($unitPets), $dates);

            $units[] = [
                'unit_type' => $unitType,
                'pets' => $unitPets,
                'nights' => count($dates),
                'subtotal' => $price['subtotal'],
                'breakdown' => $price['breakdown'],
            ];
        }

        return $units;
    }

    /**
     * Chaque nuit, les places déjà prises par les réservations en attente, confirmées ou en cours, plus celles
     * demandées, ne dépassent pas le nombre de places du type.
     *
     * @param  list<array{unit_type: ActivityUnitType}>  $units
     * @param  list<CarbonImmutable>  $dates
     */
    private function assertCapacity(Activity $activity, array $units, array $dates): void
    {
        $requested = array_count_values(array_map(fn (array $unit): string => $unit['unit_type']->id, $units));
        $occupancy = $this->unitTypes->occupancy($activity, $activity->profession->billing_unit, $dates[0], $dates[array_key_last($dates)]);

        foreach ($units as $unit) {
            $unitType = $unit['unit_type'];

            foreach ($dates as $date) {
                if (($occupancy[$unitType->id][$date->toDateString()] ?? 0) + $requested[$unitType->id] > $unitType->quantity) {
                    throw StayUnavailableException::full($date);
                }
            }
        }
    }

    /**
     * @param  list<array{service_id: string, pet_id: string, quantity?: int}>  $requested
     * @param  Collection<string, Pet>  $pets
     * @return list<array{service: Service, pet: Pet, quantity: int, unit_price: numeric-string, subtotal: numeric-string, duration_minutes: int|null, is_included: bool}>
     */
    private function priceOptions(Activity $activity, array $requested, Collection $pets): array
    {
        return array_map(
            fn (array $option, int $index): array => $this->priceOption($activity, $option['service_id'], $pets[$option['pet_id']], (int) ($option['quantity'] ?? 1), "options.{$index}.service_id"),
            $requested,
            array_keys($requested),
        );
    }

    /**
     * Option de séjour vendue par l'activité, au prix de sa grille pour l'animal, ajusté par l'activité.
     * Une option incluse dans le séjour ne coûte rien.
     *
     * @return array{service: Service, pet: Pet, quantity: int, unit_price: numeric-string, subtotal: numeric-string, duration_minutes: int|null, is_included: bool}
     *
     * @throws ValidationException option que l'activité ne vend pas pour cet animal
     */
    public function priceOption(Activity $activity, string $serviceId, Pet $pet, int $quantity, string $errorKey = 'service_id'): array
    {
        $service = $activity->services()
            ->wherePivot('is_active', true)
            ->wherePivotIn('offered_as', [ServiceOfferEnum::STAY_OPTION->value, ServiceOfferEnum::BOTH->value])
            ->where('services.is_active', true)
            ->with('prices')
            ->find($serviceId);
        $line = $service === null ? null : $this->prices->forPet($service, $pet->loadMissing(['animalType', 'animalBreed']));

        if ($service === null || $line === null) {
            throw ValidationException::withMessages([$errorKey => __('booking.option_unavailable', ['pet' => $pet->name])]);
        }

        $unitPrice = $service->offer->is_included ? '0.00' : Money::adjust($line->price, $service->offer->adjustment_percent);

        return [
            'service' => $service,
            'pet' => $pet,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => Money::multiply($unitPrice, (string) $quantity),
            'duration_minutes' => $line->duration_minutes,
            'is_included' => $service->offer->is_included,
        ];
    }

    /**
     * Chez le pro par défaut quand il reçoit, sinon chez le client, sinon à distance (rendez-vous seulement). Chez
     * le client, l'adresse vient de son carnet et doit se trouver dans le rayon d'intervention de l'activité.
     *
     * @param  array<string, mixed>  $data
     * @return array{LocationModeEnum, Address|null}
     */
    private function location(Activity $activity, User $client, array $data, bool $allowRemote): array
    {
        $location = isset($data['location'])
            ? LocationModeEnum::from((string) $data['location'])
            : ($activity->locations()[0] ?? LocationModeEnum::AT_PRO);

        if (! $activity->servesAt($location) || ($location === LocationModeEnum::REMOTE && ! $allowRemote)) {
            throw ValidationException::withMessages(['location' => __('activity.location_not_allowed')]);
        }

        if ($location !== LocationModeEnum::AT_CLIENT) {
            return [$location, null];
        }

        $address = $client->addresses()->with('address')->find($data['address_id'] ?? null)?->address;
        $origin = $activity->address()->first();

        if ($address === null) {
            throw ValidationException::withMessages(['address_id' => __('validation.required', ['attribute' => 'address_id'])]);
        }

        $reachable = $address->latitude !== null && $address->longitude !== null
            && $origin?->latitude !== null && $origin->longitude !== null
            && $this->distanceKm((float) $origin->latitude, (float) $origin->longitude, (float) $address->latitude, (float) $address->longitude) <= (float) $activity->service_radius_km;

        if (! $reachable) {
            throw ValidationException::withMessages(['address_id' => __('booking.out_of_radius')]);
        }

        return [$location, $address];
    }

    /**
     * Distance à vol d'oiseau (formule de haversine), comme la recherche.
     */
    private function distanceKm(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $a = sin($latitudeDelta / 2) ** 2 + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($longitudeDelta / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Explore;

use App\Enums\BookingModeEnum;
use App\Enums\ServiceOfferEnum;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Services\Agenda\AgendaService;
use App\Services\Pricing\ActivityPricingService;
use App\Services\Stay\UnitTypeService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AvailabilityService
{
    private const int CHUNK = 100;

    public function __construct(
        private readonly ActivityPricingService $pricing,
        private readonly UnitTypeService $unitTypes,
        private readonly AgendaService $agenda,
    ) {}

    /**
     * @param  Builder<Activity>  $candidates
     * @param  array<string, int>  $animals  [animal type id] => count
     * @return list<string>
     */
    public function availableIds(Builder $candidates, CarbonImmutable $start, CarbonImmutable $end, array $animals): array
    {
        $ids = [];

        $candidates
            ->setEagerLoads([])
            ->with('profession')
            ->select('activities.*')
            ->reorder()
            ->chunkById(self::CHUNK, function (Collection $activities) use (&$ids, $start, $end, $animals): void {
                foreach ($activities as $activity) {
                    if ($this->isAvailable($activity, $start, $end, $animals)) {
                        $ids[] = $activity->id;
                    }
                }
            }, 'activities.id', 'id');

        return $ids;
    }

    /**
     * @param  array<string, int>  $animals
     */
    public function isAvailable(Activity $activity, CarbonImmutable $start, CarbonImmutable $end, array $animals): bool
    {
        return $activity->profession?->booking_mode === BookingModeEnum::APPOINTMENT
            ? $this->hasFreeSlot($activity, $start, $end, $animals)
            : $this->canHost($activity, $start, $end, $animals);
    }

    /**
     * @param  array<string, int>  $animals
     */
    private function canHost(Activity $activity, CarbonImmutable $start, CarbonImmutable $end, array $animals): bool
    {
        $billingUnit = $activity->profession->billing_unit;
        $dates = $billingUnit->stayDates($start, $end) ?: [$start->startOfDay()];
        $first = $dates[0];
        $last = $dates[array_key_last($dates)];
        $calculator = $this->pricing->calculator($activity, $first, $last);

        if (count($dates) < $calculator->minStay($first)) {
            return false;
        }

        $unitTypes = $this->unitTypes->forActivity($activity);
        $occupancy = $this->unitTypes->occupancy($activity, $billingUnit, $first, $last);

        foreach ($dates as $date) {
            if (! $calculator->isOpen($date)) {
                return false;
            }

            $places = $unitTypes->map(fn (ActivityUnitType $unitType): array => [
                'species' => $unitType->animalTypes->modelKeys(),
                'units' => $calculator->unitPrice($unitType->id, $date) === null
                    ? 0
                    : max(0, $unitType->quantity - ($occupancy[$unitType->id][$date->toDateString()] ?? 0)),
                'animals_per_unit' => $unitType->max_animals_per_unit,
            ]);

            if (! $this->fits($places->all(), $animals)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{species: list<string>, units: int, animals_per_unit: int}>  $places
     * @param  array<string, int>  $animals
     */
    private function fits(array $places, array $animals): bool
    {
        if ($animals === []) {
            return array_sum(array_column($places, 'units')) > 0;
        }

        $capacity = fn (array $species): int => array_sum(array_map(
            fn (array $place): int => array_intersect($place['species'], $species) === [] ? 0 : $place['units'] * $place['animals_per_unit'],
            $places,
        ));

        if ($capacity(array_keys($animals)) < array_sum($animals)) {
            return false;
        }

        foreach ($animals as $animalTypeId => $count) {
            if ($capacity([$animalTypeId]) < $count) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, int>  $animals
     */
    private function hasFreeSlot(Activity $activity, CarbonImmutable $start, CarbonImmutable $end, array $animals): bool
    {
        $duration = $this->shortestDuration($activity, $animals);

        return $duration !== null && $this->agenda->slotFinder($activity, $start, $end)->find($start, $end, $duration) !== [];
    }

    /**
     * @param  array<string, int>  $animals
     */
    private function shortestDuration(Activity $activity, array $animals): ?int
    {
        $services = $activity->services()
            ->wherePivot('is_active', true)
            ->wherePivotIn('offered_as', [ServiceOfferEnum::STANDALONE->value, ServiceOfferEnum::BOTH->value])
            ->where('services.is_active', true)
            ->where('services.requires_scheduling', true)
            ->with(['prices' => fn ($query) => $query->whereNotNull('duration_minutes')])
            ->get();

        $durations = $services->map(function (Service $service) use ($animals): ?int {
            if ($animals === []) {
                return $service->prices->min('duration_minutes');
            }

            $total = 0;

            foreach ($animals as $animalTypeId => $count) {
                $minutes = $service->prices
                    ->filter(fn (ServicePrice $price): bool => $price->animal_type_id === $animalTypeId)
                    ->min('duration_minutes');

                if ($minutes === null) {
                    return null;
                }

                $total += $minutes * $count;
            }

            return $total;
        })->filter();

        return $durations->isEmpty() ? null : (int) $durations->min();
    }
}

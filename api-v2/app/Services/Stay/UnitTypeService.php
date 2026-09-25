<?php

declare(strict_types=1);

namespace App\Services\Stay;

use App\Enums\BillingUnitEnum;
use App\Enums\BookingStatusEnum;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\BookingUnit;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Places d'un séjour et leur occupation.
 */
class UnitTypeService
{
    /**
     * @return Collection<int, ActivityUnitType>
     */
    public function forActivity(Activity $activity, bool $withInactive = false): Collection
    {
        return $activity->unitTypes()
            ->with('animalTypes')
            ->when(! $withInactive, fn ($query) => $query->where('is_active', true))
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Activity $activity, array $data): ActivityUnitType
    {
        return DB::transaction(function () use ($activity, $data): ActivityUnitType {
            $unitType = $activity->unitTypes()->create(Arr::except($data, 'animal_type_ids'));
            $unitType->animalTypes()->sync($data['animal_type_ids']);

            // Rechargée pour lire les valeurs par défaut posées par la base.
            return $unitType->refresh()->load('animalTypes');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ActivityUnitType $unitType, array $data): ActivityUnitType
    {
        DB::transaction(function () use ($unitType, $data): void {
            $unitType->update(Arr::except($data, 'animal_type_ids'));

            if (isset($data['animal_type_ids'])) {
                $unitType->animalTypes()->sync($data['animal_type_ids']);
            }
        });

        return $unitType->load('animalTypes');
    }

    /**
     * Une place déjà réservée reste dans l'historique : on la désactive plutôt.
     */
    public function delete(ActivityUnitType $unitType): void
    {
        if ($unitType->bookingUnits()->exists()) {
            throw ValidationException::withMessages(['unit_type' => __('booking.unit_type_in_use')]);
        }

        $unitType->delete();
    }

    /**
     * Places occupées par type de place et par date, du $from au $to compris : celles des réservations en
     * attente, confirmées ou en cours.
     *
     * @return array<string, array<string, int>> [type de place][Y-m-d] => nombre de places
     */
    public function occupancy(Activity $activity, BillingUnitEnum $billingUnit, CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = BookingUnit::query()
            ->join('bookings', 'bookings.id', '=', 'booking_units.booking_id')
            ->where('bookings.activity_id', $activity->id)
            ->whereIn('bookings.status', BookingStatusEnum::occupying())
            ->whereDate('bookings.start_date', '<=', $to)
            ->whereDate('bookings.end_date', '>=', $from)
            ->get(['booking_units.activity_unit_type_id', 'booking_units.quantity', 'bookings.start_date', 'bookings.end_date']);

        $occupancy = [];

        foreach ($rows as $row) {
            $dates = $billingUnit->stayDates(
                CarbonImmutable::parse((string) $row->getAttribute('start_date')),
                CarbonImmutable::parse((string) $row->getAttribute('end_date')),
            );

            foreach ($dates as $date) {
                if ($date->betweenIncluded($from->toImmutable()->startOfDay(), $to->toImmutable()->startOfDay())) {
                    $day = $date->toDateString();
                    $occupancy[$row->activity_unit_type_id][$day] = ($occupancy[$row->activity_unit_type_id][$day] ?? 0) + $row->quantity;
                }
            }
        }

        return $occupancy;
    }
}

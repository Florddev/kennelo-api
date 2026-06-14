<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\BookingStatusEnum;
use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\ActivityCycleSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActivityCycleService
{
    public function list(Activity $activity): Collection
    {
        return ActivityCycle::with(['settings.animalType', 'settings.prices', 'closedWeekDays'])
            ->where('activity_id', $activity->id)
            ->orderByDesc('priority')
            ->get();
    }

    public function createCycle(Activity $activity, array $data): ActivityCycle
    {
        return DB::transaction(function () use ($activity, $data): ActivityCycle {
            $startDate = $data['start_date'] ?? null;
            $endDate = $data['end_date'] ?? null;

            $priority = array_key_exists('priority', $data)
                ? (int) $data['priority']
                : $this->nextPriorityForRange($activity, $startDate, $endDate);

            $cycle = ActivityCycle::create([
                'activity_id' => $activity->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'priority' => $priority,
                'is_active' => $data['is_active'] ?? true,
                'color' => $data['color'] ?? null,
            ]);

            return $cycle->load(['settings.animalType', 'settings.prices', 'closedWeekDays']);
        });
    }

    public function reorder(Activity $activity, array $orderedIds): Collection
    {
        DB::transaction(function () use ($activity, $orderedIds): void {
            $total = count($orderedIds);

            foreach (array_values($orderedIds) as $index => $cycleId) {
                ActivityCycle::where('activity_id', $activity->id)
                    ->where('id', $cycleId)
                    ->update(['priority' => $total - $index]);
            }
        });

        return $this->list($activity);
    }

    private function nextPriorityForRange(Activity $activity, ?string $startDate, ?string $endDate): int
    {
        if ($startDate === null && $endDate === null) {
            return 0;
        }

        $priorities = ActivityCycle::where('activity_id', $activity->id)
            ->where(fn ($query) => $query->whereNotNull('start_date')->orWhereNotNull('end_date'))
            ->get()
            ->filter(fn (ActivityCycle $cycle): bool => $this->rangesOverlap(
                $startDate,
                $endDate,
                $cycle->start_date?->toDateString(),
                $cycle->end_date?->toDateString(),
            ))
            ->pluck('priority');

        return $priorities->isEmpty() ? 1 : ((int) $priorities->max() + 1);
    }

    private function rangesOverlap(?string $aStart, ?string $aEnd, ?string $bStart, ?string $bEnd): bool
    {
        $startsBeforeOtherEnds = $aStart === null || $bEnd === null || $aStart <= $bEnd;
        $endsAfterOtherStarts = $aEnd === null || $bStart === null || $aEnd >= $bStart;

        return $startsBeforeOtherEnds && $endsAfterOtherStarts;
    }

    public function updateCycle(ActivityCycle $cycle, array $data): ActivityCycle
    {
        $cycle->update($data);

        return $cycle->fresh(['settings.animalType', 'settings.prices', 'closedWeekDays']);
    }

    public function deleteCycle(ActivityCycle $cycle): void
    {
        $cycle->delete();
    }

    public function upsertSettings(ActivityCycle $cycle, array $settings): ActivityCycle
    {
        return DB::transaction(function () use ($cycle, $settings): ActivityCycle {
            $cycle->settings()->delete();

            foreach ($settings as $setting) {
                $prices = $setting['prices'] ?? [];

                $created = ActivityCycleSetting::create([
                    'activity_cycle_id' => $cycle->id,
                    'animal_type_id' => $setting['animal_type_id'],
                    'max_capacity' => $setting['max_capacity'],
                ]);

                foreach ($prices as $price) {
                    $created->prices()->create([
                        'weekday' => (int) $price['weekday'],
                        'price' => $price['price'],
                    ]);
                }
            }

            return $cycle->fresh(['settings.animalType', 'settings.prices', 'closedWeekDays']);
        });
    }

    public function upsertClosedWeekDays(ActivityCycle $cycle, int $sumWeekdays): ActivityCycle
    {
        DB::transaction(function () use ($cycle, $sumWeekdays): void {
            $cycle->closedWeekDays()->delete();
            $cycle->closedWeekDays()->create(['sum_weekdays' => $sumWeekdays]);
        });

        return $cycle->fresh(['settings.animalType', 'settings.prices', 'closedWeekDays']);
    }

    public function resolveActiveCycle(Activity $activity, string $date): ?ActivityCycle
    {
        return ActivityCycle::with(['settings.animalType', 'settings.prices', 'closedWeekDays'])
            ->where('activity_id', $activity->id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('start_date')->orWhere('start_date', '<=', $date))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', '>=', $date))
            ->orderByDesc('priority')
            ->first();
    }

    public function getSettingsWithOccupancy(Activity $activity, ?string $date): Collection
    {
        $date = $date ?? now()->toDateString();

        $cycle = $this->resolveActiveCycle($activity, $date);

        if ($cycle === null) {
            return collect();
        }

        $settings = $cycle->settings->values();

        $occupancy = DB::table('booking_pets')
            ->join('pets', 'booking_pets.pet_id', '=', 'pets.id')
            ->join('bookings', 'bookings.id', '=', 'booking_pets.booking_id')
            ->where('bookings.activity_id', $activity->id)
            ->whereIn('bookings.status', [BookingStatusEnum::CONFIRMED->value, BookingStatusEnum::IN_PROGRESS->value])
            ->where('bookings.check_in_date', '<=', $date)
            ->where('bookings.check_out_date', '>=', $date)
            ->groupBy('pets.animal_type_id')
            ->selectRaw('pets.animal_type_id, COUNT(*) as count')
            ->pluck('count', 'pets.animal_type_id');

        return $settings->each(function (ActivityCycleSetting $setting) use ($occupancy): void {
            $setting->occupied_spots = (int) ($occupancy[$setting->animal_type_id] ?? 0);
        });
    }

    public function weekDayForDate(string $date): WeekDayEnum
    {
        return match (Carbon::parse($date)->dayOfWeekIso) {
            1 => WeekDayEnum::MONDAY,
            2 => WeekDayEnum::TUESDAY,
            3 => WeekDayEnum::WEDNESDAY,
            4 => WeekDayEnum::THURSDAY,
            5 => WeekDayEnum::FRIDAY,
            6 => WeekDayEnum::SATURDAY,
            default => WeekDayEnum::SUNDAY,
        };
    }
}

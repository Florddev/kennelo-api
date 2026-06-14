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
            $cycle = ActivityCycle::create([
                'activity_id' => $activity->id,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'priority' => $data['priority'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'color' => $data['color'] ?? null,
            ]);

            return $cycle->load(['settings.animalType', 'settings.prices', 'closedWeekDays']);
        });
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

                $weekdayMask = array_reduce(
                    $prices,
                    static fn (int $mask, array $price): int => $mask | (int) $price['weekday'],
                    WeekDayEnum::NONE,
                );

                $minPrice = $prices === []
                    ? '0'
                    : (string) min(array_map(static fn (array $price): float => (float) $price['price'], $prices));

                $created = ActivityCycleSetting::create([
                    'activity_cycle_id' => $cycle->id,
                    'animal_type_id' => $setting['animal_type_id'],
                    'max_capacity' => $setting['max_capacity'],
                    'price' => $minPrice,
                    'sum_weekdays' => $weekdayMask === WeekDayEnum::NONE ? WeekDayEnum::ALL : $weekdayMask,
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

        $weekDay = $this->weekDayForDate($date);

        $settings = $cycle->settings
            ->filter(fn (ActivityCycleSetting $setting): bool => WeekDayEnum::contains($setting->sum_weekdays, $weekDay))
            ->values();

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

    private function weekDayForDate(string $date): WeekDayEnum
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

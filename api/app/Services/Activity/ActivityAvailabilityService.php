<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\AvailabilityStatusEnum;
use App\Models\Activity;
use App\Models\ActivityAvailability;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class ActivityAvailabilityService
{
    public function storePeriod(Activity $activity, array $data): Collection
    {
        $period = CarbonPeriod::create($data['start_date'], $data['end_date']);
        $now = now()->toDateTimeString();

        $rows = collect($period)->map(fn (Carbon $date): array => [
            'activity_id' => $activity->id,
            'date' => $date->toDateString(),
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        ActivityAvailability::upsert($rows, ['activity_id', 'date'], ['status', 'note', 'updated_at']);

        return ActivityAvailability::where('activity_id', $activity->id)
            ->whereBetween('date', [$data['start_date'], $data['end_date']])
            ->orderBy('date')
            ->get();
    }

    public function update(ActivityAvailability $availability, array $data): ActivityAvailability
    {
        $availability->update($data);

        return $availability->fresh();
    }

    public function getCalendar(Activity $activity, string $month): Collection
    {
        $firstDay = Carbon::parse("{$month}-01");
        $lastDay = $firstDay->copy()->endOfMonth();

        return ActivityAvailability::where('activity_id', $activity->id)
            ->whereBetween('date', [$firstDay->toDateString(), $lastDay->toDateString()])
            ->orderBy('date')
            ->get();
    }

    public function bulk(Activity $activity, array $data): Collection
    {
        $now = now()->toDateTimeString();

        $rows = collect($data['dates'])->map(fn (string $date): array => [
            'activity_id' => $activity->id,
            'date' => $date,
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        ActivityAvailability::upsert($rows, ['activity_id', 'date'], ['status', 'note', 'updated_at']);

        return ActivityAvailability::where('activity_id', $activity->id)
            ->whereIn('date', $data['dates'])
            ->orderBy('date')
            ->get();
    }

    public function getRange(Activity $activity, string $startDate, string $endDate): Collection
    {
        $stored = ActivityAvailability::where('activity_id', $activity->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(fn (ActivityAvailability $a): string => $a->date->toDateString());

        return collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn (Carbon $date): ActivityAvailability => $stored->get($date->toDateString())
                ?? new ActivityAvailability([
                    'activity_id' => $activity->id,
                    'date' => $date->toDateString(),
                    'status' => AvailabilityStatusEnum::OPEN,
                    'note' => null,
                ])
            );
    }

    public function delete(ActivityAvailability $availability): void
    {
        $availability->delete();
    }
}

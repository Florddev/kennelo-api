<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\AvailabilityStatusEnum;
use App\Models\Activity;
use App\Models\ActivityAvailability;
use App\Models\ActivityOpeningHour;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Horaires habituels d'une activité, et exceptions sur une journée entière (fermetures, ouvertures exceptionnelles).
 */
class ActivityAvailabilityService
{
    /**
     * Remplace la semaine d'un bloc : les plages absentes de la liste sont supprimées.
     *
     * @param  list<array{weekday: int, opens_at: string, closes_at: string}>  $hours
     * @return Collection<int, ActivityOpeningHour>
     */
    public function replaceOpeningHours(Activity $activity, array $hours): Collection
    {
        DB::transaction(function () use ($activity, $hours): void {
            $activity->openingHours()->delete();
            $activity->openingHours()->createMany($hours);
        });

        return $activity->openingHours()->get();
    }

    /**
     * @return Collection<int, ActivityAvailability>
     */
    public function exceptions(Activity $activity, string $from, string $to): Collection
    {
        return $activity->availabilities()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('date')
            ->get();
    }

    /**
     * Pose la même exception sur chaque date. Une date qui en avait déjà une est remplacée.
     *
     * @param  list<string>  $dates  au format Y-m-d
     * @return Collection<int, ActivityAvailability>
     */
    public function store(Activity $activity, array $dates, AvailabilityStatusEnum $status, ?string $note): Collection
    {
        ActivityAvailability::query()->upsert(
            array_map(fn (string $date): array => [
                'activity_id' => $activity->id,
                'date' => $date,
                'status' => $status->value,
                'note' => $note,
            ], $dates),
            ['activity_id', 'date'],
            ['status', 'note', 'updated_at'],
        );

        return $activity->availabilities()->whereIn('date', $dates)->orderBy('date')->get();
    }

    public function delete(ActivityAvailability $availability): void
    {
        $availability->delete();
    }
}

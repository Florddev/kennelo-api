<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AvailabilityStatusEnum;
use App\Models\Activity;
use App\Models\ActivityAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class ActivityAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = Activity::all();
        if ($activities->isEmpty()) {
            throw new \RuntimeException('No activities found. Run ActivitySeeder first.');
        }

        $startDate = CarbonImmutable::now()->startOfMonth();
        $endDate = CarbonImmutable::now()->addMonths(3)->endOfMonth();

        $activities->each(function (Activity $activity) use ($startDate, $endDate) {
            for ($date = $startDate; $date->lte($endDate); $date = $date->addDay()) {
                ActivityAvailability::updateOrCreate(
                    [
                        'activity_id' => $activity->id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'status' => AvailabilityStatusEnum::OPEN,
                        'note' => null,
                    ],
                );
            }
        });
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActivityCycle;
use App\Models\ActivityCycleClosedWeekDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityCycleClosedWeekDay>
 */
class ActivityCycleClosedWeekDayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'activity_cycle_id' => ActivityCycle::factory(),
            'sum_weekdays' => 0,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityCycle>
 */
class ActivityCycleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'start_date' => null,
            'end_date' => null,
            'priority' => 0,
            'is_active' => true,
        ];
    }
}

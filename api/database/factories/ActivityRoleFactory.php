<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityRole>
 */
class ActivityRoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'name' => fake()->unique()->jobTitle(),
        ];
    }
}

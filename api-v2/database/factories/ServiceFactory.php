<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true),
            'is_package' => false,
            'requires_scheduling' => false,
            'is_active' => true,
        ];
    }

    public function package(): static
    {
        return $this->state(fn (): array => ['is_package' => true]);
    }
}

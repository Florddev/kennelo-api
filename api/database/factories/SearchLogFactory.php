<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SearchLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchLog>
 */
class SearchLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'location' => fake()->city().' 69000',
            'latitude' => fake()->latitude(42, 51),
            'longitude' => fake()->longitude(-4, 8),
            'department' => fake()->numerify('##'),
            'region' => 'Auvergne-Rhône-Alpes',
            'filters' => ['sort' => 'rating'],
            'results_count' => fake()->numberBetween(0, 40),
        ];
    }
}

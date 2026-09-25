<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProfessionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfessionCategory>
 */
class ProfessionCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('category_??????'),
            'name' => ['en' => 'Care', 'fr' => 'Soin'],
            'sort_order' => 0,
        ];
    }
}

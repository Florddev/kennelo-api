<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnimalType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnimalType>
 */
class AnimalTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('species_??????'),
            'name' => ['en' => 'Species', 'fr' => 'Espèce'],
            'category' => 'mammals',
        ];
    }

    public function dog(): static
    {
        return $this->state(fn (): array => ['code' => 'dog', 'name' => ['en' => 'Dog', 'fr' => 'Chien']]);
    }

    public function cat(): static
    {
        return $this->state(fn (): array => ['code' => 'cat', 'name' => ['en' => 'Cat', 'fr' => 'Chat']]);
    }
}

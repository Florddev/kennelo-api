<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnimalBreed;
use App\Models\AnimalType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnimalBreed>
 */
class AnimalBreedFactory extends Factory
{
    public function definition(): array
    {
        return [
            'animal_type_id' => AnimalType::factory(),
            'breed' => fake()->unique()->lexify('breed_??????'),
            'label' => ['en' => 'Breed', 'fr' => 'Race'],
        ];
    }
}

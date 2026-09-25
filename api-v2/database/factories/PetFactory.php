<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnimalType;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pet>
 */
class PetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'animal_type_id' => AnimalType::factory(),
            'name' => fake()->firstName(),
        ];
    }
}

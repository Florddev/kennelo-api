<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstablishmentType;
use App\Models\Address;
use App\Models\Establishment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Establishment>
 */
class EstablishmentFactory extends Factory
{
    public function definition(): array
    {
        $timezones = [
            'Europe/Paris',
            'Europe/London',
        ];

        return [
            'name' => fake()->company(),
            'siret' => fake()->numerify('##############'),
            'type' => fake()->randomElement(EstablishmentType::cases()),
            'description' => fake()->optional(0.8)->sentence(10),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => fake()->optional(0.6)->url(),
            'timezone' => fake()->randomElement($timezones),
            'is_active' => fake()->boolean(85),
            'address_id' => Address::factory(),
            'manager_id' => User::factory(),
        ];
    }
}

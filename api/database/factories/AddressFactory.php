<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    public function definition(): array
    {
        $latitude = fake()->latitude(40, 50);
        $longitude = fake()->longitude(-5, 10);

        return [
            'line1' => fake()->streetAddress(),
            'line2' => fake()->optional(0.3)->secondaryAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'region' => fake()->state(),
            'country' => fake()->countryCode(),
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }
}

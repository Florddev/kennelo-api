<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProspectSourceEnum;
use App\Enums\ProspectStatusEnum;
use App\Models\Prospect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prospect>
 */
class ProspectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->numerify('69###'),
            'department' => '69',
            'region' => 'Auvergne-Rhône-Alpes',
            'country' => 'FR',
            'latitude' => fake()->latitude(45, 46),
            'longitude' => fake()->longitude(4, 5),
            'phone' => fake()->numerify('+336########'),
            'website' => fake()->optional(0.7)->url(),
            'google_rating' => fake()->optional(0.8)->randomFloat(1, 3, 5),
            'google_reviews_count' => fake()->optional(0.8)->numberBetween(0, 300),
            'google_place_id' => 'ChIJ'.fake()->unique()->bothify('####??####??####'),
            'category' => fake()->randomElement(['Pension pour chiens', 'Pension pour chats', "Garde d'animaux"]),
            'animal_types' => null,
            'services' => null,
            'siret' => null,
            'siren' => null,
            'ape_code' => null,
            'status' => ProspectStatusEnum::NON_CONTACTE->value,
            'source' => ProspectSourceEnum::APIFY->value,
            'assigned_to' => null,
            'kennelo_activity_id' => null,
        ];
    }

    public function registered(): static
    {
        return $this->state(fn (): array => [
            'status' => ProspectStatusEnum::INSCRIT->value,
        ]);
    }
}

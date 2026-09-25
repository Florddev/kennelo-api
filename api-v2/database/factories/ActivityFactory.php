<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityStatusEnum;
use App\Models\Activity;
use App\Models\Address;
use App\Models\AnimalType;
use App\Models\Organization;
use App\Models\Profession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Par défaut, une activité en attente de validation, qui reçoit chez elle.
 *
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'profession_id' => Profession::factory(),
            'name' => fake()->company(),
            'address_id' => Address::factory(),
            'timezone' => 'Europe/Paris',
            'serves_at_pro' => true,
            'serves_at_client' => false,
            'serves_remote' => false,
        ];
    }

    public function approved(): static
    {
        return $this->afterMaking(function (Activity $activity): void {
            $activity->forceFill(['status' => ActivityStatusEnum::APPROVED, 'reviewed_at' => now()]);
        });
    }

    /**
     * Réservable par un client : approuvée, dans une entreprise vérifiée qui peut encaisser.
     */
    public function bookable(): static
    {
        return $this->approved()->state(fn (): array => [
            'organization_id' => Organization::factory()->verified()->withStripe(),
        ]);
    }

    /**
     * Adresse géolocalisée à ce point.
     */
    public function at(float $latitude, float $longitude): static
    {
        return $this->state(fn (): array => [
            'address_id' => Address::factory()->state(['latitude' => $latitude, 'longitude' => $longitude]),
        ]);
    }

    /**
     * Se déplace chez le client, dans ce rayon.
     */
    public function atClient(int $radiusKm, bool $alsoAtPro = false): static
    {
        return $this->state(fn (): array => [
            'serves_at_pro' => $alsoAtPro,
            'serves_at_client' => true,
            'service_radius_km' => $radiusKm,
        ]);
    }

    public function forSpecies(AnimalType ...$animalTypes): static
    {
        return $this->afterCreating(fn (Activity $activity) => $activity->animalTypes()->attach(
            array_map(fn (AnimalType $animalType): string => $animalType->id, $animalTypes),
        ));
    }
}

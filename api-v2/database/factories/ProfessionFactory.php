<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BillingUnitEnum;
use App\Enums\BookingModeEnum;
use App\Enums\DocumentTypeEnum;
use App\Models\AnimalType;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Par défaut, un métier en rendez-vous exercé chez le pro ou chez le client, sans espèce ni justificatif.
 *
 * @extends Factory<Profession>
 */
class ProfessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'profession_category_id' => ProfessionCategory::factory(),
            'code' => fake()->unique()->lexify('profession_??????'),
            'name' => ['en' => 'Grooming', 'fr' => 'Toilettage'],
            'booking_mode' => BookingModeEnum::APPOINTMENT,
            'billing_unit' => BillingUnitEnum::SLOT,
            'allows_at_pro' => true,
            'allows_at_client' => true,
            'allows_remote' => false,
            'pricing_dimensions' => [],
            'is_active' => true,
        ];
    }

    public function stay(): static
    {
        return $this->state(fn (): array => [
            'booking_mode' => BookingModeEnum::STAY,
            'billing_unit' => BillingUnitEnum::NIGHT,
        ]);
    }

    public function forSpecies(AnimalType ...$animalTypes): static
    {
        return $this->afterCreating(fn (Profession $profession) => $profession->animalTypes()->attach(
            array_map(fn (AnimalType $animalType): string => $animalType->id, $animalTypes),
        ));
    }

    public function requiring(DocumentTypeEnum $type, ?int $validityMonths = null, bool $required = true): static
    {
        return $this->afterCreating(fn (Profession $profession) => $profession->documentRequirements()->create([
            'document_type' => $type,
            'is_required' => $required,
            'validity_months' => $validityMonths,
        ]));
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityPeriodSetting;
use App\Models\ActivityUnitType;
use App\Models\AnimalType;
use App\Models\PricingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityUnitType>
 */
class ActivityUnitTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'name' => 'Box',
            'quantity' => 2,
            'max_animals_per_unit' => 1,
        ];
    }

    public function forSpecies(AnimalType ...$animalTypes): static
    {
        return $this->afterCreating(fn (ActivityUnitType $unitType) => $unitType->animalTypes()->attach(
            array_map(fn (AnimalType $animalType): string => $animalType->id, $animalTypes),
        ));
    }

    /**
     * Prix de la place pour tous les jours, dans la grille de la période (de base par défaut) de l'activité.
     */
    public function priced(string $price, ?string $extraAnimalPrice = null, ?PricingPeriod $period = null): static
    {
        return $this->afterCreating(function (ActivityUnitType $unitType) use ($price, $extraAnimalPrice, $period): void {
            $activity = $unitType->activity()->firstOrFail();
            $period ??= PricingPeriod::query()->where('organization_id', $activity->organization_id)->base()->firstOrFail();

            $setting = ActivityPeriodSetting::query()
                ->where('activity_id', $activity->id)
                ->where('pricing_period_id', $period->id)
                ->first()
                ?? tap((new ActivityPeriodSetting)->forceFill([
                    'organization_id' => $activity->organization_id,
                    'activity_id' => $activity->id,
                    'pricing_period_id' => $period->id,
                ]))->save();

            $setting->prices()->create([
                'activity_unit_type_id' => $unitType->id,
                'price' => $price,
                'extra_animal_price' => $extraAnimalPrice,
            ]);
        });
    }
}

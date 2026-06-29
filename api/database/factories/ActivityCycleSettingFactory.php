<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WeekDayEnum;
use App\Models\ActivityCycle;
use App\Models\ActivityCycleSetting;
use App\Models\AnimalType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityCycleSetting>
 */
class ActivityCycleSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'activity_cycle_id' => ActivityCycle::factory(),
            'animal_type_id' => fn (): string => (string) AnimalType::query()->inRandomOrder()->value('id'),
            'max_capacity' => fake()->numberBetween(5, 30),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ActivityCycleSetting $setting): void {
            $price = fake()->randomFloat(2, 15, 60);

            foreach (WeekDayEnum::values() as $weekday) {
                $setting->prices()->create(['weekday' => $weekday, 'price' => $price]);
            }
        });
    }
}

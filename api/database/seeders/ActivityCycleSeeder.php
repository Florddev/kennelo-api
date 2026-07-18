<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\ActivityCycleSetting;
use App\Models\AnimalType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityCycleSeeder extends Seeder
{
    private const MAX_CAPACITY = 20;

    private array $price = [
        'dog' => 30.00,
        'cat' => 25.00,
        'bird' => 20.00,
    ];

    public function run(): void
    {
        $activities = Activity::all();
        if ($activities->isEmpty()) {
            throw new \RuntimeException('No activities found. Run ActivitySeeder first.');
        }

        $animalTypes = AnimalType::all();
        if ($animalTypes->isEmpty()) {
            throw new \RuntimeException('No animal types found. Run AnimalTypeSeeder first.');
        }

        DB::transaction(function () use ($activities, $animalTypes): void {
            $activities->each(function (Activity $activity) use ($animalTypes) {
                $cycle = ActivityCycle::firstOrCreate(
                    ['activity_id' => $activity->id, 'priority' => 0],
                    ['start_date' => null, 'end_date' => null, 'is_active' => true],
                );

                $chosenTypes = $animalTypes->shuffle()->take(random_int(1, $animalTypes->count()));

                $chosenTypes->each(function ($animalType) use ($cycle) {
                    $setting = ActivityCycleSetting::firstOrCreate(
                        [
                            'activity_cycle_id' => $cycle->id,
                            'animal_type_id' => $animalType->id,
                        ],
                        [
                            'max_capacity' => self::MAX_CAPACITY,
                        ]
                    );

                    if ($setting->prices()->doesntExist()) {
                        $price = $this->price[$animalType->code] ?? 25.00;

                        $now = now();
                        $priceRows = array_map(fn (int $weekday): array => [
                            'id' => (string) Str::uuid(),
                            'activity_cycle_setting_id' => $setting->id,
                            'weekday' => $weekday,
                            'price' => $price,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ], WeekDayEnum::values());

                        $setting->prices()->insert($priceRows);
                    }
                });
            });
        });
    }
}

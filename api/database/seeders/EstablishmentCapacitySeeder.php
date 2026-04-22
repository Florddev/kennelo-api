<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnimalType;
use App\Models\Establishment;
use App\Models\EstablishmentCapacity;
use Illuminate\Database\Seeder;

class EstablishmentCapacitySeeder extends Seeder
{
    public function run(): void
    {
        $establishments = Establishment::all();
        if ($establishments->isEmpty()) {
            throw new \RuntimeException('No establishments found. Run EstablishmentSeeder first.');
        }

        $animalTypes = AnimalType::all()->keyBy('code');
        if ($animalTypes->isEmpty()) {
            throw new \RuntimeException('No animal types found. Run AnimalTypeSeeder first.');
        }

        $pricingByCode = [
            'dog' => [20, 45, 6, 14],
            'cat' => [15, 30, 4, 10],
            'rabbit' => [10, 20, 3, 8],
            'bird' => [8, 18, 3, 8],
            'hamster' => [6, 12, 2, 6],
            'guinea_pig' => [6, 14, 2, 6],
            'ferret' => [12, 22, 2, 5],
            'fish' => [5, 10, 1, 3],
            'reptile' => [12, 25, 1, 4],
            'amphibian' => [10, 20, 1, 3],
        ];

        $establishments->each(function (Establishment $establishment) use ($animalTypes, $pricingByCode) {
            $availableCodes = collect($pricingByCode)->keys()
                ->filter(fn ($code) => $animalTypes->has($code))
                ->shuffle();

            $count = random_int(2, min(4, $availableCodes->count()));
            $chosen = $availableCodes->take($count);

            foreach ($chosen as $code) {
                [$minPrice, $maxPrice, $minCapacity, $maxCapacity] = $pricingByCode[$code];

                EstablishmentCapacity::updateOrCreate(
                    [
                        'establishment_id' => $establishment->id,
                        'animal_type_id' => $animalTypes->get($code)->id,
                    ],
                    [
                        'max_capacity' => random_int($minCapacity, $maxCapacity),
                        'price_per_night' => random_int($minPrice * 100, $maxPrice * 100) / 100,
                    ],
                );
            }
        });
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnimalType;
use App\Models\Establishment;
use App\Models\EstablishmentCapacity;
use Illuminate\Database\Seeder;

class EstablishmentCapacitySeeder extends Seeder
{
    private const MAX_CAPACITY = 20;

    private array $pricePerNight = [
        'dog' => 30.00,
        'cat' => 25.00,
        'bird' => 20.00,
    ];

    public function run(): void
    {
        $establishments = Establishment::all();
        if ($establishments->isEmpty()) {
            throw new \RuntimeException('No establishments found. Run EstablishmentSeeder first.');
        }

        $animalTypes = AnimalType::all();
        if ($animalTypes->isEmpty()) {
            throw new \RuntimeException('No animal types found. Run AnimalTypeSeeder first.');
        }

        $establishments->each(function ($establishment) use ($animalTypes) {
            $animalTypes->each(function ($animalType) use ($establishment) {
                EstablishmentCapacity::firstOrCreate(
                    [
                        'establishment_id' => $establishment->id,
                        'animal_type_id' => $animalType->id,
                    ],
                    [
                        'max_capacity' => self::MAX_CAPACITY,
                        'price_per_night' => $this->pricePerNight[$animalType->code] ?? 25.00,
                    ]
                );
            });
        });
    }
}

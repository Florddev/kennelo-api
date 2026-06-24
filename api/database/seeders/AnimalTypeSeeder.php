<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnimalType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class AnimalTypeSeeder extends Seeder
{
    public function run(): void
    {
        $animalTypes = json_decode(File::get(database_path('data/animal_types.translated.json')), true);

        foreach ($animalTypes as $animalType) {
            AnimalType::updateOrCreate(
                ['code' => $animalType['code']],
                [
                    'name' => $animalType['name'],
                    'category' => $animalType['category'],
                ]
            );
        }
    }
}

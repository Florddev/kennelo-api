<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnimalBreed;
use App\Models\AnimalType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AnimalBreedSeeder extends Seeder
{
    public function run(): void
    {
        $animalTypeIds = AnimalType::query()->pluck('id', 'code');
        $files = File::glob(database_path('data/breeds/*.translated.json'));

        foreach ($files as $file) {
            $code = Str::of(basename($file))->before('.translated.json')->value();
            $animalTypeId = $animalTypeIds->get($code);

            if ($animalTypeId === null) {
                $this->command->warn(sprintf('Skipping breeds for unknown animal type "%s".', $code));

                continue;
            }

            $breeds = json_decode(File::get($file), true);

            foreach ($breeds as $breed) {
                AnimalBreed::updateOrCreate(
                    [
                        'animal_type_id' => $animalTypeId,
                        'breed' => $breed['breed'],
                    ],
                    ['label' => $breed['label']]
                );
            }
        }
    }
}

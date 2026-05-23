<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnimalType;
use Illuminate\Database\Seeder;

class AnimalTypeSeeder extends Seeder
{
    public function run(): void
    {
        $animalTypes = [
            ['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals'],
            ['code' => 'cat', 'name' => 'Chat', 'category' => 'mammals'],
            ['code' => 'rabbit', 'name' => 'Lapin', 'category' => 'small_mammals'],
            ['code' => 'rodent', 'name' => 'Rongeur', 'category' => 'small_mammals'],
            ['code' => 'ferret', 'name' => 'Furet', 'category' => 'small_mammals'],
            ['code' => 'bird', 'name' => 'Oiseau', 'category' => 'birds'],
            ['code' => 'reptile', 'name' => 'Reptile', 'category' => 'reptiles'],
            ['code' => 'amphibian', 'name' => 'Amphibien', 'category' => 'amphibians'],
        ];

        foreach ($animalTypes as $animalType) {
            AnimalType::updateOrCreate(
                ['code' => $animalType['code']],
                $animalType
            );
        }
    }
}

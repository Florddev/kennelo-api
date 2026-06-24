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
            ['code' => 'dog', 'name' => ['en' => 'Dog', 'fr' => 'Chien', 'ar' => 'كلب'], 'category' => 'mammals'],
            ['code' => 'cat', 'name' => ['en' => 'Cat', 'fr' => 'Chat', 'ar' => 'قط'], 'category' => 'mammals'],
            ['code' => 'rabbit', 'name' => ['en' => 'Rabbit', 'fr' => 'Lapin', 'ar' => 'أرنب'], 'category' => 'small_mammals'],
            ['code' => 'rodent', 'name' => ['en' => 'Rodent', 'fr' => 'Rongeur', 'ar' => 'قارض'], 'category' => 'small_mammals'],
            ['code' => 'ferret', 'name' => ['en' => 'Ferret', 'fr' => 'Furet', 'ar' => 'نمس'], 'category' => 'small_mammals'],
            ['code' => 'bird', 'name' => ['en' => 'Bird', 'fr' => 'Oiseau', 'ar' => 'طائر'], 'category' => 'birds'],
            ['code' => 'reptile', 'name' => ['en' => 'Reptile', 'fr' => 'Reptile', 'ar' => 'زاحف'], 'category' => 'reptiles'],
            ['code' => 'amphibian', 'name' => ['en' => 'Amphibian', 'fr' => 'Amphibien', 'ar' => 'برمائي'], 'category' => 'amphibians'],
        ];

        foreach ($animalTypes as $animalType) {
            AnimalType::updateOrCreate(
                ['code' => $animalType['code']],
                $animalType
            );
        }
    }
}

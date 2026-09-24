<?php

declare(strict_types=1);

namespace Database\Seeders\Reference;

use App\Models\AnimalType;
use Database\Seeders\Reference\Concerns\LoadsReferenceData;
use Illuminate\Database\Seeder;

/**
 * Espèces. Source de vérité : database/data/animal_types.translated.json (généré par `php artisan data:translate`).
 * Synchronise la base avec le fichier : crée les espèces manquantes et met à jour les libellés.
 */
class AnimalTypeSeeder extends Seeder
{
    use LoadsReferenceData;

    public function run(): void
    {
        foreach ($this->referenceData('animal_types.translated.json') as $type) {
            AnimalType::query()->updateOrCreate(
                ['code' => $type['code']],
                ['category' => $type['category'], 'name' => $type['name']],
            );
        }
    }
}

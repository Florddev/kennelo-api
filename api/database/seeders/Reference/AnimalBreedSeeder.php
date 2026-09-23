<?php

declare(strict_types=1);

namespace Database\Seeders\Reference;

use App\Models\AnimalBreed;
use App\Models\AnimalType;
use Database\Seeders\Reference\Concerns\LoadsReferenceData;
use Illuminate\Database\Seeder;

/**
 * Races, par espèce. Source de vérité : database/data/breeds/{espèce}.translated.json.
 * Synchronise la base avec les fichiers : crée les races manquantes et met à jour les libellés.
 */
class AnimalBreedSeeder extends Seeder
{
    use LoadsReferenceData;

    public function run(): void
    {
        $animalTypeIds = AnimalType::query()->pluck('id', 'code');

        foreach ($animalTypeIds as $code => $animalTypeId) {
            $file = "breeds/{$code}.translated.json";

            // Toutes les espèces n'ont pas de liste de races (le cheval, par exemple).
            if (! $this->hasReferenceData($file)) {
                continue;
            }

            foreach ($this->referenceData($file) as $breed) {
                AnimalBreed::query()->updateOrCreate(
                    ['animal_type_id' => $animalTypeId, 'breed' => $breed['breed']],
                    ['label' => $breed['label']],
                );
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders\Reference;

use App\Models\AnimalType;
use App\Models\AttributeDefinition;
use Database\Seeders\Reference\Concerns\LoadsReferenceData;
use Illuminate\Database\Seeder;

/**
 * Attributs de la fiche d'un animal, leurs options et les espèces concernées.
 * Source de vérité : database/data/attribute_definitions.translated.json (généré par `php artisan data:translate`).
 * Synchronise la base avec le fichier. Une option retirée du fichier n'est pas supprimée :
 * elle peut être utilisée par des fiches existantes.
 */
class AttributeSeeder extends Seeder
{
    use LoadsReferenceData;

    public function run(): void
    {
        $animalTypeIds = AnimalType::query()->pluck('id', 'code');

        foreach ($this->referenceData('attribute_definitions.translated.json') as $data) {
            $definition = AttributeDefinition::query()->updateOrCreate(
                ['code' => $data['code']],
                [
                    'label' => $data['label'],
                    'category' => $data['category'],
                    'value_type' => $data['value_type'],
                    'input_type' => $data['input_type'] ?? null,
                    'icon_name' => $data['icon_name'] ?? null,
                    'has_predefined_options' => $data['options'] !== [],
                ],
            );

            $definition->animalTypes()->sync($this->animalTypeIdsFor($data, $animalTypeIds->all()));

            foreach ($data['options'] as $position => $option) {
                $definition->options()->updateOrCreate(
                    ['value' => $option['value']],
                    ['label' => $option['label'], 'sort_order' => $position],
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $animalTypeIds
     * @return list<string>
     */
    private function animalTypeIdsFor(array $data, array $animalTypeIds): array
    {
        $ids = [];

        foreach ($data['animal_types'] as $code) {
            if (! isset($animalTypeIds[$code])) {
                $this->command->warn(sprintf('Espèce inconnue « %s » pour l\'attribut « %s », ignorée.', $code, $data['code']));

                continue;
            }

            $ids[] = $animalTypeIds[$code];
        }

        return $ids;
    }
}

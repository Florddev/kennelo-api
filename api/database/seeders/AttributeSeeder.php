<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnimalType;
use App\Models\AttributeAnimalType;
use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $animalTypeIds = AnimalType::query()->pluck('id', 'code');
        $definitions = json_decode(File::get(database_path('data/attribute_definitions.translated.json')), true);

        DB::transaction(function () use ($definitions, $animalTypeIds): void {
            foreach ($definitions as $definition) {
                $attribute = AttributeDefinition::updateOrCreate(
                    ['code' => $definition['code']],
                    [
                        'label' => $definition['label'],
                        'category' => $definition['category'],
                        'value_type' => $definition['value_type'],
                        'input_type' => $definition['input_type'],
                        'icon_name' => $definition['icon_name'],
                        'has_predefined_options' => ! empty($definition['options']),
                        'is_required' => false,
                        'validation_rules' => null,
                    ]
                );

                foreach ($definition['animal_types'] as $code) {
                    $animalTypeId = $animalTypeIds->get($code);

                    if ($animalTypeId === null) {
                        $this->command->warn(sprintf('Skipping unknown animal type "%s" for attribute "%s".', $code, $definition['code']));

                        continue;
                    }

                    AttributeAnimalType::firstOrCreate([
                        'attribute_definition_id' => $attribute->id,
                        'animal_type_id' => $animalTypeId,
                    ]);
                }

                foreach ($definition['options'] as $index => $option) {
                    AttributeOption::updateOrCreate(
                        [
                            'attribute_definition_id' => $attribute->id,
                            'value' => $option['value'],
                        ],
                        [
                            'label' => $option['label'],
                            'sort_order' => $index,
                        ]
                    );
                }
            }
        });
    }
}

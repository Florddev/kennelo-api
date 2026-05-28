<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReviewCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $criteria = [
            [
                'code' => 'cleanliness',
                'label' => 'Propreté',
                'applicable_to' => 'establishment',
                'sort_order' => 1,
            ],
            [
                'code' => 'communication',
                'label' => 'Communication',
                'applicable_to' => 'establishment',
                'sort_order' => 2,
            ],
            [
                'code' => 'animal_care',
                'label' => 'Soins apportés à l\'animal',
                'applicable_to' => 'establishment',
                'sort_order' => 3,
            ],
            [
                'code' => 'instructions_respect',
                'label' => 'Respect des consignes',
                'applicable_to' => 'establishment',
                'sort_order' => 4,
            ],
            [
                'code' => 'value_for_money',
                'label' => 'Rapport qualité/prix',
                'applicable_to' => 'establishment',
                'sort_order' => 5,
            ],
            [
                'code' => 'environment',
                'label' => 'Environnement et espace',
                'applicable_to' => 'establishment',
                'sort_order' => 6,
            ],
            [
                'code' => 'reactivity',
                'label' => 'Réactivité et disponibilité',
                'applicable_to' => 'establishment',
                'sort_order' => 7,
            ],
            [
                'code' => 'info_accuracy',
                'label' => 'Exactitude des informations',
                'applicable_to' => 'user',
                'sort_order' => 1,
            ],
            [
                'code' => 'punctuality',
                'label' => 'Ponctualité',
                'applicable_to' => 'user',
                'sort_order' => 2,
            ],
            [
                'code' => 'animal_condition',
                'label' => 'État de l\'animal à l\'arrivée',
                'applicable_to' => 'user',
                'sort_order' => 3,
            ],
            [
                'code' => 'animal_behavior',
                'label' => 'Comportement de l\'animal (conforme aux infos)',
                'applicable_to' => 'user',
                'sort_order' => 4,
            ],
        ];

        foreach ($criteria as $item) {
            $exists = DB::table('review_criteria_definitions')->where('code', $item['code'])->exists();
            if ($exists) {
                DB::table('review_criteria_definitions')
                    ->where('code', $item['code'])
                    ->update(array_merge($item, ['updated_at' => now()]));
            } else {
                DB::table('review_criteria_definitions')->insert(array_merge($item, [
                    'id' => (string) Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
}

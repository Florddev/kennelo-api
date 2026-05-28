<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CriteriaApplicableTo;
use App\Models\ReviewCriteriaDefinition;
use Illuminate\Database\Seeder;

class ReviewCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $criteria = [
            ['code' => 'cleanliness', 'label' => 'Propreté', 'applicable_to' => CriteriaApplicableTo::ESTABLISHMENT, 'sort_order' => 1],
            ['code' => 'communication', 'label' => 'Communication', 'applicable_to' => CriteriaApplicableTo::ESTABLISHMENT, 'sort_order' => 2],
            ['code' => 'animal_care', 'label' => 'Soins apportés à l\'animal', 'applicable_to' => CriteriaApplicableTo::ESTABLISHMENT, 'sort_order' => 3],
            ['code' => 'instructions_respect', 'label' => 'Respect des consignes', 'applicable_to' => CriteriaApplicableTo::ESTABLISHMENT, 'sort_order' => 4],
            ['code' => 'value_for_money', 'label' => 'Rapport qualité/prix', 'applicable_to' => CriteriaApplicableTo::ESTABLISHMENT, 'sort_order' => 5],
            ['code' => 'environment', 'label' => 'Environnement et espace', 'applicable_to' => CriteriaApplicableTo::ESTABLISHMENT, 'sort_order' => 6],
            ['code' => 'reactivity', 'label' => 'Réactivité et disponibilité', 'applicable_to' => CriteriaApplicableTo::ESTABLISHMENT, 'sort_order' => 7],
            ['code' => 'info_accuracy', 'label' => 'Exactitude des informations', 'applicable_to' => CriteriaApplicableTo::USER, 'sort_order' => 1],
            ['code' => 'punctuality', 'label' => 'Ponctualité', 'applicable_to' => CriteriaApplicableTo::USER, 'sort_order' => 2],
            ['code' => 'animal_condition', 'label' => 'État de l\'animal à l\'arrivée', 'applicable_to' => CriteriaApplicableTo::USER, 'sort_order' => 3],
            ['code' => 'animal_behavior', 'label' => 'Comportement de l\'animal (conforme aux infos)', 'applicable_to' => CriteriaApplicableTo::USER, 'sort_order' => 4],
        ];

        foreach ($criteria as $item) {
            ReviewCriteriaDefinition::updateOrCreate(
                ['code' => $item['code']],
                ['label' => $item['label'], 'applicable_to' => $item['applicable_to'], 'sort_order' => $item['sort_order']]
            );
        }
    }
}

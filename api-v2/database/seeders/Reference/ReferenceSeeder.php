<?php

declare(strict_types=1);

namespace Database\Seeders\Reference;

use Illuminate\Database\Seeder;

/**
 * Données de référence, nécessaires au fonctionnement de l'application dans tous les environnements.
 *
 * Rejoué à chaque déploiement : chaque seeder est idempotent et ne crée jamais de doublon.
 * L'ordre suit les dépendances (les métiers et les attributs référencent les espèces).
 */
class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SubscriptionPlanSeeder::class,
            AnimalTypeSeeder::class,
            AnimalBreedSeeder::class,
            AttributeSeeder::class,
            ProfessionSeeder::class,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Fake\FakeSeeder;
use Database\Seeders\Reference\ReferenceSeeder;
use Illuminate\Database\Seeder;

/**
 * Point d'entrée de `php artisan db:seed`.
 *
 * - Reference : données nécessaires au fonctionnement de l'application, dans tous les environnements.
 * - Fake : données fictives de développement et de démonstration, jamais en production.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceSeeder::class);

        if (! app()->isProduction()) {
            $this->call(FakeSeeder::class);
        }
    }
}

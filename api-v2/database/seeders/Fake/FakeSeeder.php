<?php

declare(strict_types=1);

namespace Database\Seeders\Fake;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Données fictives pour le développement et les démonstrations : utilisateurs, entreprises, réservations…
 *
 * Jamais exécuté en production : Faker n'y est pas installé, et ces données n'ont rien à y faire.
 */
class FakeSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Les données fictives ne peuvent pas être générées en production.');
        }

        $this->call(DemoSeeder::class);
    }
}

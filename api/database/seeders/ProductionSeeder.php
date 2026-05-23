<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AnimalTypeSeeder::class,
            AttributeSeeder::class,
            ReviewCriteriaSeeder::class,
        ]);
    }
}

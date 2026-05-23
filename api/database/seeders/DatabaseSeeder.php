<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionSeeder::class);

        if (app()->environment(['local', 'development', 'staging'])) {
            $this->call(DevelopmentSeeder::class);
        }
    }
}

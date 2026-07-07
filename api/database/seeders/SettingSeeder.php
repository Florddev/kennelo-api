<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Setting\SettingService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        app(SettingService::class)->seedDefaults();
    }
}

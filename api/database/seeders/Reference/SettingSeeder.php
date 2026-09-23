<?php

declare(strict_types=1);

namespace Database\Seeders\Reference;

use App\Services\Setting\SettingService;
use Illuminate\Database\Seeder;

/**
 * Réglages de la plateforme. Les clés et leurs valeurs par défaut sont définies dans SettingService.
 * Crée les réglages manquants, n'écrase jamais une valeur modifiée dans le back-office.
 */
class SettingSeeder extends Seeder
{
    public function run(SettingService $settings): void
    {
        $settings->seedDefaults();
    }
}

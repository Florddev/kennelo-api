<?php

declare(strict_types=1);

use App\Services\Setting\SettingService;

if (! function_exists('setting')) {
    /**
     * Valeur d'un réglage de la plateforme : celle modifiée dans le back-office, sinon la valeur par défaut de la config.
     */
    function setting(string $key): mixed
    {
        return app(SettingService::class)->get($key);
    }
}

if (! function_exists('department_from_postal_code')) {
    function department_from_postal_code(?string $postalCode): ?string
    {
        if (blank($postalCode) || strlen($postalCode) < 2) {
            return null;
        }

        $prefix = (string) str($postalCode)->substr(0, 2);

        if ($prefix === '20') {
            return in_array((string) str($postalCode)->substr(0, 3), ['200', '201'], true) ? '2A' : '2B';
        }

        if (str($postalCode)->startsWith(['97', '98'])) {
            return (string) str($postalCode)->substr(0, 3);
        }

        return $prefix;
    }
}

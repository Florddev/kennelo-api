<?php

declare(strict_types=1);

use App\Services\Setting\SettingService;
use Illuminate\Support\Carbon;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingService::class)->get($key, $default);
    }
}

if (! function_exists('type_to_class')) {
    function type_to_class(string $type): ?string
    {
        $type = (string) str($type)->ucfirst()->lower();

        $className = 'App\\Models\\'.$type;
        if (class_exists($className)) {
            return $className;
        }

        $singular = str($type)->singular();
        $className = 'App\\Models\\'.$singular;
        if (class_exists($className)) {
            return $className;
        }

        $plural = str($type)->plural();
        $className = 'App\\Models\\'.$plural;
        if (class_exists($className)) {
            return $className;
        }

        return null;
    }
}

if (! function_exists('is_admin')) {
    function is_admin(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        try {
            return auth()->user()->hasRole('admin');
        } catch (Exception $e) {
            return false;
        }
    }
}

if (! function_exists('human_date')) {
    function human_date($date): string
    {
        if (is_null($date)) {
            return '';
        }

        $originalDate = $date;

        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        Carbon::setLocale('fr');
        $date->setTimezone('Europe/Paris');

        $now = now()->setTimezone('Europe/Paris');

        $hasTime = false;

        if (is_string($originalDate)) {
            $hasTime = preg_match('/\d{2}:\d{2}(:\d{2})?/', $originalDate);
        } else {
            $hasTime = $date->format('H:i:s') !== '00:00:00';
        }

        $timeFormat = $hasTime ? ' à '.$date->format('H:i') : '';

        if ($date->isSameDay($now)) {
            return "Aujourd'hui".$timeFormat;
        }

        if ($date->isYesterday()) {
            return 'Hier'.$timeFormat;
        }

        $frenchMonths = [
            '01' => 'janvier',
            '02' => 'février',
            '03' => 'mars',
            '04' => 'avril',
            '05' => 'mai',
            '06' => 'juin',
            '07' => 'juillet',
            '08' => 'août',
            '09' => 'septembre',
            '10' => 'octobre',
            '11' => 'novembre',
            '12' => 'décembre',
        ];

        $month = $frenchMonths[$date->format('m')];

        if ($date->isSameYear($now)) {
            return $date->format('d').' '.$month.$timeFormat;
        }

        return $date->format('d').' '.$month.' '.$date->format('Y').$timeFormat;
    }
}

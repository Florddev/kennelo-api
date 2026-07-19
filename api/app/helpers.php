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

        return rescue(fn (): bool => auth()->user()->hasRole('admin'), false, report: false);
    }
}

if (! function_exists('human_date')) {
    function human_date(DateTimeInterface|string|null $date): string
    {
        if (blank($date)) {
            return '';
        }

        $parsed = rescue(fn (): Carbon => Carbon::parse($date), report: false);

        if ($parsed === null) {
            return '';
        }

        $timezone = (string) config('app.display_timezone', 'Europe/Paris');
        $hasTime = $parsed->format('H:i:s') !== '00:00:00';

        $parsed = $parsed->setTimezone($timezone)->locale(app()->getLocale());

        $time = $hasTime ? (string) __('dates.at', ['time' => $parsed->format('H:i')]) : '';

        return match (true) {
            $parsed->isToday() => (string) __('dates.today').$time,
            $parsed->isYesterday() => (string) __('dates.yesterday').$time,
            $parsed->isSameYear(now($timezone)) => $parsed->isoFormat((string) __('dates.day_month')).$time,
            default => $parsed->isoFormat((string) __('dates.day_month_year')).$time,
        };
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

if (! function_exists('region_from_department')) {
    function region_from_department(?string $department): ?string
    {
        if (blank($department)) {
            return null;
        }

        $regions = [
            'Auvergne-Rhône-Alpes' => ['01', '03', '07', '15', '26', '38', '42', '43', '63', '69', '73', '74'],
            'Bourgogne-Franche-Comté' => ['21', '25', '39', '58', '70', '71', '89', '90'],
            'Bretagne' => ['22', '29', '35', '56'],
            'Centre-Val de Loire' => ['18', '28', '36', '37', '41', '45'],
            'Corse' => ['2A', '2B'],
            'Grand Est' => ['08', '10', '51', '52', '54', '55', '57', '67', '68', '88'],
            'Hauts-de-France' => ['02', '59', '60', '62', '80'],
            'Île-de-France' => ['75', '77', '78', '91', '92', '93', '94', '95'],
            'Normandie' => ['14', '27', '50', '61', '76'],
            'Nouvelle-Aquitaine' => ['16', '17', '19', '23', '24', '33', '40', '47', '64', '79', '86', '87'],
            'Occitanie' => ['09', '11', '12', '30', '31', '32', '34', '46', '48', '65', '66', '81', '82'],
            'Pays de la Loire' => ['44', '49', '53', '72', '85'],
            "Provence-Alpes-Côte d'Azur" => ['04', '05', '06', '13', '83', '84'],
            'Guadeloupe' => ['971'],
            'Martinique' => ['972'],
            'Guyane' => ['973'],
            'La Réunion' => ['974'],
            'Mayotte' => ['976'],
        ];

        foreach ($regions as $region => $departments) {
            if (in_array($department, $departments, true)) {
                return $region;
            }
        }

        return null;
    }
}

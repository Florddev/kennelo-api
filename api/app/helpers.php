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

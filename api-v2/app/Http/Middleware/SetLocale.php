<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = explode(',', config('app.available_locales', 'en'));
        $defaultLocale = config('app.locale', 'en');

        $locale = $this->resolveUserLocale($availableLocales)
            ?? $this->parseAcceptLanguage($request->header('Accept-Language'), $availableLocales)
            ?? $defaultLocale;

        App::setLocale($locale);

        return $next($request);
    }

    private function resolveUserLocale(array $availableLocales): ?string
    {
        try {
            $user = Auth::guard('web')->user();
        } catch (Throwable) {
            return null;
        }

        if ($user && in_array($user->locale, $availableLocales, true)) {
            return $user->locale;
        }

        return null;
    }

    private function parseAcceptLanguage(?string $acceptLanguage, array $availableLocales): ?string
    {
        if (! $acceptLanguage) {
            return null;
        }

        $locale = trim(strtok($acceptLanguage, ',;'));

        if (strlen($locale) > 2) {
            $locale = (string) str($locale)->substr(0, 2);
        }

        return in_array($locale, $availableLocales, true) ? $locale : null;
    }
}

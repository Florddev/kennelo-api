<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

/**
 * Règles d'un libellé traduit, envoyé sous la forme { "en": "…", "fr": "…" }. Seules les langues de
 * l'application sont acceptées, et la langue de repli est obligatoire : c'est elle qui s'affiche
 * quand une traduction manque.
 */
trait ValidatesTranslations
{
    /**
     * @param  'required'|'sometimes'|'nullable'  $presence
     * @return array<string, array<int, string>>
     */
    protected function translationRules(string $field, string $presence, int $max): array
    {
        $locales = config('app.available_locales');
        $fallback = config('app.fallback_locale');

        return [
            $field => [$presence, "array:{$locales}"],
            "{$field}.{$fallback}" => ["required_with:{$field}", 'string', "max:{$max}"],
            "{$field}.*" => ['nullable', 'string', "max:{$max}"],
        ];
    }
}

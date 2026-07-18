<?php

declare(strict_types=1);

namespace App\Services\Translation;

use Illuminate\Support\Facades\Http;

class DeeplTranslator
{
    public function isConfigured(): bool
    {
        return filled(config('services.deepl.key'));
    }

    public function sourceLocale(): string
    {
        return (string) config('services.deepl.source_locale');
    }

    /**
     * @return array<int, string>
     */
    public function availableLocales(): array
    {
        return collect(explode(',', (string) config('app.available_locales')))
            ->map(static fn (string $locale): string => trim($locale))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function targetLocales(): array
    {
        return collect($this->availableLocales())
            ->diff([$this->sourceLocale()])
            ->values()
            ->all();
    }

    public function translate(string $text, string $source, string $target): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'DeepL-Auth-Key '.config('services.deepl.key'),
        ])->asForm()->post(config('services.deepl.url'), [
            'text' => $text,
            'source_lang' => (string) str($source)->upper(),
            'target_lang' => (string) str($target)->upper(),
        ])->throw();

        return $response->json('translations.0.text', $text);
    }

    /**
     * Fill any missing target-locale translations for the given label map.
     *
     * @param  array<string, string>  $label
     * @return array{label: array<string, string>, translated: int}
     */
    public function fillMissing(array $label, string $sourceText): array
    {
        $source = $this->sourceLocale();
        $label[$source] = $label[$source] ?? $sourceText;

        $translated = 0;

        foreach ($this->targetLocales() as $target) {
            if (! empty($label[$target])) {
                continue;
            }

            $label[$target] = $this->translate($label[$source], $source, $target);
            $translated++;
        }

        return ['label' => $label, 'translated' => $translated];
    }
}

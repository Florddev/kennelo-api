<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TranslateBreedsCommand extends Command
{
    protected $signature = 'breeds:translate';

    protected $description = 'Fill the breeds translation cache by translating missing labels through DeepL';

    public function handle(): int
    {
        $key = config('services.deepl.key');

        if (empty($key)) {
            $this->error('DEEPL_API_KEY is not configured.');

            return self::FAILURE;
        }

        $sourceLocale = config('services.deepl.source_locale');
        $locales = $this->locales();
        $targetLocales = array_values(array_diff($locales, [$sourceLocale]));

        $directory = database_path('data/breeds');
        $sourceFiles = File::glob($directory.'/*.json');

        $translatedCount = 0;

        foreach ($sourceFiles as $sourceFile) {
            if (Str::endsWith($sourceFile, '.translated.json')) {
                continue;
            }

            $type = Str::of(basename($sourceFile))->before('.json')->value();
            $cacheFile = $directory.'/'.$type.'.translated.json';

            $source = json_decode(File::get($sourceFile), true);
            $cache = File::exists($cacheFile)
                ? collect(json_decode(File::get($cacheFile), true))->keyBy('breed')->all()
                : [];

            foreach ($source as $entry) {
                $slug = $entry['breed'];
                $label = $cache[$slug]['label'] ?? [];

                $label[$sourceLocale] = $label[$sourceLocale] ?? Str::headline($slug);

                foreach ($targetLocales as $targetLocale) {
                    if (! empty($label[$targetLocale])) {
                        continue;
                    }

                    $label[$targetLocale] = $this->translate($label[$sourceLocale], $sourceLocale, $targetLocale);
                    $translatedCount++;

                    $this->line(sprintf('  %s [%s] -> %s', $slug, $targetLocale, $label[$targetLocale]));
                }

                $cache[$slug] = ['breed' => $slug, 'label' => $label];
            }

            $ordered = array_map(static fn (array $entry): array => $cache[$entry['breed']], $source);

            File::put($cacheFile, json_encode($ordered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);

            $this->info(sprintf('Updated %s (%d breeds).', basename($cacheFile), count($ordered)));
        }

        $this->info(sprintf('Done. %d labels translated.', $translatedCount));

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function locales(): array
    {
        return collect(explode(',', (string) config('app.available_locales')))
            ->map(static fn (string $locale): string => trim($locale))
            ->filter()
            ->values()
            ->all();
    }

    private function translate(string $text, string $source, string $target): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'DeepL-Auth-Key '.config('services.deepl.key'),
        ])->asForm()->post(config('services.deepl.url'), [
            'text' => $text,
            'source_lang' => strtoupper($source),
            'target_lang' => strtoupper($target),
        ])->throw();

        return $response->json('translations.0.text', $text);
    }
}

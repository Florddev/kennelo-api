<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Translation\DeeplTranslator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TranslateBreedsCommand extends Command
{
    protected $signature = 'breeds:translate';

    protected $description = 'Fill the breeds translation cache by translating missing labels through DeepL';

    public function handle(DeeplTranslator $translator): int
    {
        if (! $translator->isConfigured()) {
            $this->error('DEEPL_API_KEY is not configured.');

            return self::FAILURE;
        }

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
                $result = $translator->fillMissing($cache[$slug]['label'] ?? [], Str::headline($slug));
                $translatedCount += $result['translated'];
                $cache[$slug] = ['breed' => $slug, 'label' => $result['label']];
            }

            $ordered = array_map(static fn (array $entry): array => $cache[$entry['breed']], $source);

            File::put($cacheFile, json_encode($ordered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);

            $this->info(sprintf('Updated %s (%d breeds).', basename($cacheFile), count($ordered)));
        }

        $this->info(sprintf('Done. %d labels translated.', $translatedCount));

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Translation\DeeplTranslator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TranslateAnimalTypesCommand extends Command
{
    protected $signature = 'animal-types:translate';

    protected $description = 'Fill the animal types translation cache by translating missing names through DeepL';

    public function handle(DeeplTranslator $translator): int
    {
        if (! $translator->isConfigured()) {
            $this->error('DEEPL_API_KEY is not configured.');

            return self::FAILURE;
        }

        $sourceFile = database_path('data/animal_types.json');
        $cacheFile = database_path('data/animal_types.translated.json');

        $source = json_decode(File::get($sourceFile), true);
        $cache = File::exists($cacheFile)
            ? collect(json_decode(File::get($cacheFile), true))->keyBy('code')->all()
            : [];

        $translatedCount = 0;
        $ordered = [];

        foreach ($source as $entry) {
            $code = $entry['code'];
            $result = $translator->fillMissing($cache[$code]['name'] ?? [], $entry['name']);
            $translatedCount += $result['translated'];

            $ordered[] = [
                'code' => $code,
                'category' => $entry['category'],
                'name' => $result['label'],
            ];
        }

        File::put($cacheFile, json_encode($ordered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $this->info(sprintf('Updated %s (%d types, %d labels translated).', basename($cacheFile), count($ordered), $translatedCount));

        return self::SUCCESS;
    }
}

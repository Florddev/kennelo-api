<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Translation\DeeplTranslator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TranslateAttributesCommand extends Command
{
    protected $signature = 'attributes:translate';

    protected $description = 'Fill the attribute definitions/options translation cache through DeepL';

    public function handle(DeeplTranslator $translator): int
    {
        if (! $translator->isConfigured()) {
            $this->error('DEEPL_API_KEY is not configured.');

            return self::FAILURE;
        }

        $sourceFile = database_path('data/attribute_definitions.json');
        $cacheFile = database_path('data/attribute_definitions.translated.json');

        $source = json_decode(File::get($sourceFile), true);
        $cache = File::exists($cacheFile)
            ? collect(json_decode(File::get($cacheFile), true))->keyBy('code')->all()
            : [];

        $translatedCount = 0;
        $ordered = [];

        foreach ($source as $definition) {
            $code = $definition['code'];
            $cached = $cache[$code] ?? [];

            $labelResult = $translator->fillMissing($cached['label'] ?? [], $definition['label']);
            $translatedCount += $labelResult['translated'];

            $cachedOptions = collect($cached['options'] ?? [])->keyBy('value')->all();
            $options = [];

            foreach ($definition['options'] as $option) {
                $optionResult = $translator->fillMissing(
                    $cachedOptions[$option['value']]['label'] ?? [],
                    $option['label']
                );
                $translatedCount += $optionResult['translated'];

                $options[] = ['value' => $option['value'], 'label' => $optionResult['label']];
            }

            $ordered[] = [
                'code' => $code,
                'category' => $definition['category'],
                'value_type' => $definition['value_type'],
                'input_type' => $definition['input_type'],
                'icon_name' => $definition['icon_name'],
                'animal_types' => $definition['animal_types'],
                'label' => $labelResult['label'],
                'options' => $options,
            ];
        }

        File::put($cacheFile, json_encode($ordered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $this->info(sprintf('Updated %s (%d definitions, %d labels translated).', basename($cacheFile), count($ordered), $translatedCount));

        return self::SUCCESS;
    }
}

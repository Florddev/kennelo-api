<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Dedoc\Scramble\Console\Commands\Concerns\RendersDiagnostics;
use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportOpenApiCommand extends Command
{
    use RendersDiagnostics;

    protected $signature = 'openapi:export {--check : Fail when openapi.json is out of date, without writing it}';

    protected $description = 'Export the OpenAPI document of the API to openapi.json';

    public function handle(Generator $generator): int
    {
        ini_set('memory_limit', '512M');

        config([
            'database.connections.openapi' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.default' => 'openapi',
            'cache.default' => 'array',
        ]);

        $this->callSilently('migrate', ['--database' => 'openapi', '--force' => true]);

        $result = $generator->generate(Scramble::getGeneratorConfig(Scramble::DEFAULT_API));
        $document = json_encode($result->spec(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
        $path = base_path('openapi.json');

        $this->renderDiagnostics(
            $result,
            'OpenAPI document generated.',
            fn (string $summary): string => "OpenAPI document generated with {$summary}.",
        );

        if (! $this->option('check')) {
            File::put($path, $document);
            $this->info('Exported to openapi.json.');

            return self::SUCCESS;
        }

        if (! File::exists($path) || File::get($path) !== $document) {
            $this->error('openapi.json is out of date: run php artisan openapi:export and commit it.');

            return self::FAILURE;
        }

        $this->info('openapi.json is up to date.');

        return self::SUCCESS;
    }
}

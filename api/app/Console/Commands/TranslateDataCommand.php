<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TranslateDataCommand extends Command
{
    protected $signature = 'data:translate';

    protected $description = 'Run every reference-data translation command (breeds, animal types, attributes)';

    private const COMMANDS = [
        'animal-types:translate',
        'breeds:translate',
        'attributes:translate',
    ];

    public function handle(): int
    {
        foreach (self::COMMANDS as $command) {
            $this->info("Running {$command}...");

            if ($this->call($command) !== self::SUCCESS) {
                $this->error("{$command} failed. Aborting.");

                return self::FAILURE;
            }
        }

        $this->info('All reference-data translations are up to date.');

        return self::SUCCESS;
    }
}

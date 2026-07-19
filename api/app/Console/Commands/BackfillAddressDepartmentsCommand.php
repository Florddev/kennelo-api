<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Address;
use Illuminate\Console\Command;

class BackfillAddressDepartmentsCommand extends Command
{
    protected $signature = 'kennelo:backfill-address-departments';

    protected $description = 'Fill the missing department column on addresses from their postal code';

    public function handle(): int
    {
        $updated = 0;

        Address::query()
            ->whereNull('department')
            ->whereNotNull('postal_code')
            ->chunkById(200, function ($addresses) use (&$updated): void {
                foreach ($addresses as $address) {
                    $department = department_from_postal_code($address->postal_code);

                    if ($department === null) {
                        continue;
                    }

                    $address->update(['department' => $department]);
                    $updated++;
                }
            });

        $this->info("{$updated} adresses mises à jour.");

        return self::SUCCESS;
    }
}

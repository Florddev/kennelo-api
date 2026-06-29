<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Scanner;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScannerSeeder extends Seeder
{
    /** @var array<int, array{code: string, name: string}> */
    private array $scanners = [
        ['code' => 'SCANNER-RECEPTION', 'name' => 'Reception scanner'],
        ['code' => 'SCANNER-OUTDOOR', 'name' => 'Outdoor scanner'],
        ['code' => 'SCANNER-MOBILE', 'name' => 'Mobile scanner'],
    ];

    public function run(): void
    {
        $manager = User::where('email', 'manager@orus.com')->first();
        if (! $manager) {
            throw new \RuntimeException('Manager manager@orus.com not found. Run UsersSeeder first.');
        }

        foreach ($this->scanners as $scanner) {
            Scanner::firstOrCreate(
                ['code' => $scanner['code']],
                [
                    'user_id' => $manager->id,
                    'name' => $scanner['name'],
                ]
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AvailabilityStatus;
use App\Models\Establishment;
use App\Models\EstablishmentAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class EstablishmentAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $establishments = Establishment::all();
        if ($establishments->isEmpty()) {
            throw new \RuntimeException('No establishments found. Run EstablishmentSeeder first.');
        }

        $startDate = CarbonImmutable::now()->startOfMonth();
        $endDate = CarbonImmutable::now()->addMonths(3)->endOfMonth();

        $establishments->each(function (Establishment $establishment) use ($startDate, $endDate) {
            for ($date = $startDate; $date->lte($endDate); $date = $date->addDay()) {
                EstablishmentAvailability::updateOrCreate(
                    [
                        'establishment_id' => $establishment->id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'status' => AvailabilityStatus::OPEN,
                        'note' => null,
                    ],
                );
            }
        });
    }
}

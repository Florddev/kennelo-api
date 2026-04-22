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
    private const DAYS_AHEAD = 120;

    public function run(): void
    {
        $establishments = Establishment::all();
        if ($establishments->isEmpty()) {
            throw new \RuntimeException('No establishments found. Run EstablishmentSeeder first.');
        }

        $today = CarbonImmutable::now()->startOfDay();

        $establishments->each(function (Establishment $establishment) use ($today) {
            $closedWeekdays = collect([0, 1, 2, 3, 4, 5, 6])
                ->shuffle()
                ->take(random_int(0, 1))
                ->values()
                ->all();

            for ($offset = 0; $offset < self::DAYS_AHEAD; $offset++) {
                $date = $today->addDays($offset);
                $isWeekendClosed = in_array($date->dayOfWeek, $closedWeekdays, true);
                $isRandomClosed = random_int(1, 100) <= 5;
                $status = $isWeekendClosed || $isRandomClosed
                    ? AvailabilityStatus::CLOSED
                    : AvailabilityStatus::OPEN;

                EstablishmentAvailability::updateOrCreate(
                    [
                        'establishment_id' => $establishment->id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'status' => $status,
                        'note' => null,
                    ],
                );
            }
        });
    }
}

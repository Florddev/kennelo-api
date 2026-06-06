<?php

declare(strict_types=1);

namespace App\Services\Establishment;

use App\Enums\AvailabilityStatusEnum;
use App\Models\Establishment;
use App\Models\EstablishmentAvailability;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class EstablishmentAvailabilityService
{
    public function storePeriod(Establishment $establishment, array $data): Collection
    {
        $period = CarbonPeriod::create($data['start_date'], $data['end_date']);
        $now = now()->toDateTimeString();

        $rows = collect($period)->map(fn (Carbon $date): array => [
            'establishment_id' => $establishment->id,
            'date' => $date->toDateString(),
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        EstablishmentAvailability::upsert($rows, ['establishment_id', 'date'], ['status', 'note', 'updated_at']);

        return EstablishmentAvailability::where('establishment_id', $establishment->id)
            ->whereBetween('date', [$data['start_date'], $data['end_date']])
            ->orderBy('date')
            ->get();
    }

    public function update(EstablishmentAvailability $availability, array $data): EstablishmentAvailability
    {
        $availability->update($data);

        return $availability->fresh();
    }

    public function getCalendar(Establishment $establishment, string $month): Collection
    {
        $firstDay = Carbon::parse("{$month}-01");
        $lastDay = $firstDay->copy()->endOfMonth();

        return EstablishmentAvailability::where('establishment_id', $establishment->id)
            ->whereBetween('date', [$firstDay->toDateString(), $lastDay->toDateString()])
            ->orderBy('date')
            ->get();
    }

    public function bulk(Establishment $establishment, array $data): Collection
    {
        return collect($data['dates'])->map(function (string $date) use ($establishment, $data): EstablishmentAvailability {
            return EstablishmentAvailability::updateOrCreate(
                ['establishment_id' => $establishment->id, 'date' => $date],
                ['status' => $data['status'], 'note' => $data['note'] ?? null],
            );
        });
    }

    public function getRange(Establishment $establishment, string $startDate, string $endDate): Collection
    {
        $stored = EstablishmentAvailability::where('establishment_id', $establishment->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(fn (EstablishmentAvailability $a): string => $a->date->toDateString());

        return collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn (Carbon $date): EstablishmentAvailability => $stored->get($date->toDateString())
                ?? new EstablishmentAvailability([
                    'establishment_id' => $establishment->id,
                    'date' => $date->toDateString(),
                    'status' => AvailabilityStatusEnum::OPEN,
                    'note' => null,
                ])
            );
    }

    public function delete(EstablishmentAvailability $availability): void
    {
        $availability->delete();
    }
}

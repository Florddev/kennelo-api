<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\AvailabilityStatusEnum;
use App\Models\Activity;
use App\Models\ActivityAvailability;
use App\Models\ActivityCycleSetting;

class ActivityDashboardService
{
    public function __construct(private ActivityCycleService $cycleService) {}

    public function getDashboard(Activity $activity): array
    {
        $today = now()->toDateString();

        $settings = $this->cycleService->getSettingsWithOccupancy($activity, $today);

        $totalCapacity = (int) $settings->sum('max_capacity');
        $totalOccupied = (int) $settings->sum('occupied_spots');

        $todayStatus = ActivityAvailability::where('activity_id', $activity->id)
            ->where('date', $today)
            ->value('status') ?? AvailabilityStatusEnum::OPEN->value;

        $occupancyByAnimal = $settings->map(function (ActivityCycleSetting $setting): array {
            $occupiedSpots = $setting->occupied_spots;

            return [
                'animal_type' => [
                    'id' => $setting->animalType->id,
                    'code' => $setting->animalType->code,
                    'name' => $setting->animalType->name,
                    'category' => $setting->animalType->category,
                ],
                'max_capacity' => $setting->max_capacity,
                'occupied_spots' => $occupiedSpots,
                'available_spots' => $setting->max_capacity - $occupiedSpots,
                'occupancy_rate' => $setting->max_capacity > 0
                    ? round($occupiedSpots / $setting->max_capacity * 100, 1)
                    : 0.0,
            ];
        })->values()->all();

        return [
            'summary' => [
                'total_capacity' => $totalCapacity,
                'occupied_spots' => $totalOccupied,
                'available_spots' => $totalCapacity - $totalOccupied,
                'today_status' => $todayStatus,
            ],
            'occupancy_by_animal' => $occupancyByAnimal,
            'upcoming_bookings' => [],
        ];
    }
}

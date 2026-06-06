<?php

declare(strict_types=1);

namespace App\Services\Establishment;

use App\Enums\AvailabilityStatusEnum;
use App\Models\Establishment;
use App\Models\EstablishmentAvailability;
use App\Models\EstablishmentCapacity;

class EstablishmentDashboardService
{
    public function __construct(private EstablishmentCapacityService $capacityService) {}

    public function getDashboard(Establishment $establishment): array
    {
        $today = now()->toDateString();

        $capacities = $this->capacityService->getCapacitiesWithOccupancy($establishment, $today);

        $totalCapacity = (int) $capacities->sum('max_capacity');
        $totalOccupied = (int) $capacities->sum('occupied_spots');

        $todayStatus = EstablishmentAvailability::where('establishment_id', $establishment->id)
            ->where('date', $today)
            ->value('status') ?? AvailabilityStatusEnum::OPEN->value;

        $occupancyByAnimal = $capacities->map(function (EstablishmentCapacity $capacity): array {
            $occupiedSpots = $capacity->occupied_spots;

            return [
                'animal_type' => [
                    'id' => $capacity->animalType->id,
                    'code' => $capacity->animalType->code,
                    'name' => $capacity->animalType->name,
                    'category' => $capacity->animalType->category,
                ],
                'max_capacity' => $capacity->max_capacity,
                'occupied_spots' => $occupiedSpots,
                'available_spots' => $capacity->max_capacity - $occupiedSpots,
                'occupancy_rate' => $capacity->max_capacity > 0
                    ? round($occupiedSpots / $capacity->max_capacity * 100, 1)
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

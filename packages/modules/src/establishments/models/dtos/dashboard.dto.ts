import type { AnimalTypeDto } from "./capacity.dto";

export type DashboardSummaryDto = {
    total_capacity: number;
    occupied_spots: number;
    available_spots: number;
    today_status: "open" | "closed";
};

export type OccupancyByAnimalDto = {
    animal_type: AnimalTypeDto;
    max_capacity: number;
    occupied_spots: number;
    available_spots: number;
    occupancy_rate: number;
};

export type DashboardDto = {
    summary: DashboardSummaryDto;
    occupancy_by_animal: OccupancyByAnimalDto[];
    upcoming_bookings: unknown[];
};

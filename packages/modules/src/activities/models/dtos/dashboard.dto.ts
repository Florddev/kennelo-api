import type { AnimalTypeDto } from "./animal-type.dto";

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

export type RevenuePointDto = {
    month: string;
    amount: number;
};

export type DashboardRevenueDto = {
    current_month: number;
    previous_month: number;
    change_rate: number | null;
    currency: string;
    series: RevenuePointDto[];
};

export type DashboardDto = {
    summary: DashboardSummaryDto;
    occupancy_by_animal: OccupancyByAnimalDto[];
    revenue: DashboardRevenueDto;
    upcoming_bookings: unknown[];
};

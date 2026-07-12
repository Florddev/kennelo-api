import type { DashboardDto } from "./dtos/dashboard.dto";
import { AnimalTypeModel } from "./animal-type.model";

export class DashboardSummaryModel {
    private constructor(
        public readonly totalCapacity: number,
        public readonly occupiedSpots: number,
        public readonly availableSpots: number,
        public readonly todayStatus: "open" | "closed",
    ) {}

    static from(dto: DashboardDto["summary"]): DashboardSummaryModel {
        return new DashboardSummaryModel(
            dto.total_capacity,
            dto.occupied_spots,
            dto.available_spots,
            dto.today_status,
        );
    }
}

export class OccupancyByAnimalModel {
    private constructor(
        public readonly animalType: AnimalTypeModel,
        public readonly maxCapacity: number,
        public readonly occupiedSpots: number,
        public readonly availableSpots: number,
        public readonly occupancyRate: number,
    ) {}

    static from(dto: DashboardDto["occupancy_by_animal"][number]): OccupancyByAnimalModel {
        return new OccupancyByAnimalModel(
            AnimalTypeModel.from(dto.animal_type),
            dto.max_capacity,
            dto.occupied_spots,
            dto.available_spots,
            dto.occupancy_rate,
        );
    }
}

export type RevenuePoint = {
    month: string;
    amount: number;
};

export class DashboardRevenueModel {
    private constructor(
        public readonly currentMonth: number,
        public readonly previousMonth: number,
        public readonly changeRate: number | null,
        public readonly currency: string,
        public readonly series: RevenuePoint[],
    ) {}

    static from(dto: DashboardDto["revenue"]): DashboardRevenueModel {
        return new DashboardRevenueModel(
            dto.current_month,
            dto.previous_month,
            dto.change_rate,
            dto.currency,
            dto.series.map((point) => ({ month: point.month, amount: point.amount })),
        );
    }
}

export class DashboardModel {
    private constructor(
        public readonly summary: DashboardSummaryModel,
        public readonly occupancyByAnimal: OccupancyByAnimalModel[],
        public readonly revenue: DashboardRevenueModel,
    ) {}

    static from(dto: DashboardDto): DashboardModel {
        return new DashboardModel(
            DashboardSummaryModel.from(dto.summary),
            dto.occupancy_by_animal.map(OccupancyByAnimalModel.from),
            DashboardRevenueModel.from(dto.revenue),
        );
    }
}

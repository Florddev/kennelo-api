import type { DashboardDto } from "./dtos/dashboard.dto";
import { AnimalTypeModel } from "./capacity.model";

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

export class DashboardModel {
    private constructor(
        public readonly summary: DashboardSummaryModel,
        public readonly occupancyByAnimal: OccupancyByAnimalModel[],
    ) {}

    static from(dto: DashboardDto): DashboardModel {
        return new DashboardModel(
            DashboardSummaryModel.from(dto.summary),
            dto.occupancy_by_animal.map(OccupancyByAnimalModel.from),
        );
    }
}

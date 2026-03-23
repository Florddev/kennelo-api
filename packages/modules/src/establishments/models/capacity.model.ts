import type { CapacityDto } from "./dtos/capacity.dto";

export class AnimalTypeModel {
    private constructor(
        public readonly id: number,
        public readonly code: string,
        public readonly name: string,
        public readonly category: string,
    ) {}

    static from(dto: {
        id: number;
        code: string;
        name: string;
        category: string;
    }): AnimalTypeModel {
        return new AnimalTypeModel(dto.id, dto.code, dto.name, dto.category);
    }
}

export class CapacityModel {
    private constructor(
        public readonly id: number,
        public readonly animalType: AnimalTypeModel,
        public readonly maxCapacity: number,
        public readonly pricePerNight: number,
        public readonly occupiedSpots: number,
        public readonly availableSpots: number,
    ) {}

    static from(dto: CapacityDto): CapacityModel {
        return new CapacityModel(
            dto.id,
            AnimalTypeModel.from(dto.animal_type),
            dto.max_capacity,
            parseFloat(dto.price_per_night),
            dto.occupied_spots,
            dto.available_spots,
        );
    }
}

import type { ServiceDto } from "./dtos/service.dto";

export class ServiceModel {
    private constructor(
        public readonly id: string,
        public readonly activityId: string,
        public readonly animalTypeId: string,
        public readonly name: string,
        public readonly description: string | null,
        public readonly isIncluded: boolean,
        public readonly price: string | null,
    ) {}

    static from(dto: ServiceDto): ServiceModel {
        return new ServiceModel(
            dto.id,
            dto.activity_id,
            dto.animal_type_id,
            dto.name,
            dto.description,
            dto.is_included,
            dto.price,
        );
    }
}

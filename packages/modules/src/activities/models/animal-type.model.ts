import type { AnimalTypeDto } from "./dtos/animal-type.dto";

export class AnimalTypeModel {
    private constructor(
        public readonly id: string,
        public readonly code: string,
        public readonly name: string,
        public readonly category: string,
    ) {}

    static from(dto: AnimalTypeDto): AnimalTypeModel {
        return new AnimalTypeModel(dto.id, dto.code, dto.name, dto.category);
    }
}

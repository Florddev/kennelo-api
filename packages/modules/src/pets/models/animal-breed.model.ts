import type { AnimalBreedDto } from "./dtos/animal-breed.dto";

export class AnimalBreedModel {
    private constructor(
        public readonly id: string,
        public readonly animalTypeId: string,
        public readonly breed: string,
        public readonly label: string,
    ) {}

    static from(dto: AnimalBreedDto): AnimalBreedModel {
        return new AnimalBreedModel(dto.id, dto.animal_type_id, dto.breed, dto.label);
    }
}

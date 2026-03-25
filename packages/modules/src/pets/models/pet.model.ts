import type { PetDto } from "./dtos/pet.dto";
import { AnimalTypeModel } from "./animal-type.model";
import { PetAttributeModel } from "./pet-attribute.model";
import { PetImageModel } from "./pet-image.model";

export class PetModel {
    private constructor(
        public readonly id: string,
        public readonly userId: string,
        public readonly animalTypeId: number,
        public readonly name: string,
        public readonly breed: string | null,
        public readonly birthDate: string | null,
        public readonly sex: "male" | "female" | "unknown" | null,
        public readonly weight: number | null,
        public readonly isSterilized: boolean | null,
        public readonly hasMicrochip: boolean,
        public readonly microchipNumber: string | null,
        public readonly adoptionDate: string | null,
        public readonly about: string | null,
        public readonly healthNotes: string | null,
        public readonly avatarUrl: string | null,
        public readonly animalType: AnimalTypeModel | null,
        public readonly attributes: PetAttributeModel[] | null,
        public readonly images: PetImageModel[],
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: PetDto): PetModel {
        return new PetModel(
            dto.id,
            dto.user_id,
            dto.animal_type_id,
            dto.name,
            dto.breed,
            dto.birth_date,
            dto.sex,
            dto.weight,
            dto.is_sterilized,
            dto.has_microchip,
            dto.microchip_number,
            dto.adoption_date,
            dto.about,
            dto.health_notes,
            dto.avatar_url ?? null,
            dto.animal_type ? AnimalTypeModel.from(dto.animal_type) : null,
            dto.attributes ? dto.attributes.map(PetAttributeModel.from) : null,
            dto.images ? dto.images.map(PetImageModel.from) : [],
            dto.created_at,
            dto.updated_at,
        );
    }

    getAvatarUrl(): string | undefined {
        if (this.avatarUrl) {
            return this.avatarUrl;
        }
        if (this.images[0] != null) {
            return this.images[0].url;
        }
        return undefined;
    }
}

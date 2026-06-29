import { AnimalTypeModel } from "../../pets/models/animal-type.model";
import type { InCarePetDto } from "./dtos/in-care-pet.dto";

export class InCarePetModel {
    private constructor(
        public readonly id: string,
        public readonly name: string,
        public readonly breed: string | null,
        public readonly microchipNumber: string | null,
        public readonly hasMicrochip: boolean,
        public readonly avatarUrl: string | null,
        public readonly animalType: AnimalTypeModel | null,
        public readonly ownerName: string | null,
        public readonly activityName: string | null,
        public readonly checkInDate: string,
        public readonly checkOutDate: string,
    ) {}

    static from(dto: InCarePetDto): InCarePetModel {
        return new InCarePetModel(
            dto.id,
            dto.name,
            dto.breed,
            dto.microchip_number,
            dto.has_microchip,
            dto.avatar_url,
            dto.animal_type ? AnimalTypeModel.from(dto.animal_type) : null,
            dto.owner_name,
            dto.activity_name,
            dto.check_in_date,
            dto.check_out_date,
        );
    }
}

import type { AnimalTypeDto } from "../../../pets/models/dtos/animal-type.dto";

export type InCarePetDto = {
    id: string;
    name: string;
    breed: string | null;
    microchip_number: string | null;
    has_microchip: boolean;
    avatar_url: string | null;
    animal_type: AnimalTypeDto | null;
    owner_name: string | null;
    activity_name: string | null;
    check_in_date: string;
    check_out_date: string;
};

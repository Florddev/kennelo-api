import type { AnimalTypeDto } from "./animal-type.dto";
import type { PetAttributeDto } from "./pet-attribute.dto";
import type { PetImageDto } from "./pet-image.dto";

export type PetDto = {
    id: string;
    user_id: string;
    animal_type_id: string;
    name: string;
    breed: string | null;
    birth_date: string | null;
    sex: "male" | "female" | "unknown" | null;
    weight: number | null;
    is_sterilized: boolean | null;
    has_microchip: boolean;
    microchip_number: string | null;
    adoption_date: string | null;
    about: string | null;
    health_notes: string | null;
    avatar_url: string | null;
    animal_type?: AnimalTypeDto | null;
    attributes?: PetAttributeDto[];
    images?: PetImageDto[];
    created_at: string;
    updated_at: string;
};

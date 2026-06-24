import { api } from "@workspace/common";
import type { PetDto } from "../../models/dtos/pet.dto";
import { PetModel } from "../../models/pet.model";
import type { CreatePetInput } from "../../validators/create-pet.schema";

export async function createPet(input: CreatePetInput): Promise<PetModel> {
    const response = await api.post<PetDto>("/pets", {
        animal_type_id: input.animalTypeId,
        animal_breed_id: input.animalBreedId,
        name: input.name,
        breed: input.breed,
        birth_date: input.birthDate,
        sex: input.sex,
        weight: input.weight,
        is_sterilized: input.isSterilized,
        has_microchip: input.hasMicrochip,
        microchip_number: input.microchipNumber,
        adoption_date: input.adoptionDate,
        about: input.about,
        health_notes: input.healthNotes,
    });

    if (!response.data) {
        throw new Error("Failed to create pet");
    }

    return PetModel.from(response.data);
}

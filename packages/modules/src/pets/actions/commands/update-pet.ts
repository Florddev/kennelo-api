import { api } from "@workspace/common";
import type { PetDto } from "../../models/dtos/pet.dto";
import { PetModel } from "../../models/pet.model";
import type { UpdatePetInput } from "../../validators/update-pet.schema";

export async function updatePet(id: string, input: UpdatePetInput): Promise<PetModel> {
    const response = await api.put<PetDto>(`/pets/${id}`, {
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
        throw new Error("Failed to update pet");
    }

    return PetModel.from(response.data);
}

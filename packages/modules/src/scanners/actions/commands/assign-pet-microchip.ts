import { api } from "@workspace/common";

import type { PetDto } from "../../../pets/models/dtos/pet.dto";
import { PetModel } from "../../../pets/models/pet.model";

export async function assignPetMicrochip(
    petId: string,
    microchipNumber: string,
): Promise<PetModel> {
    const response = await api.put<PetDto>(`/hosting/pets/${petId}/microchip`, {
        microchip_number: microchipNumber,
    });

    if (!response.data) {
        throw new Error("Failed to assign microchip");
    }

    return PetModel.from(response.data);
}

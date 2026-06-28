import { api } from "@workspace/common";
import { type PetDto } from "../../models/dtos/pet.dto";
import { PetModel } from "../../models/pet.model";

export async function getPetByMicrochip(microchipNumber: string): Promise<PetModel> {
    const response = await api.get<PetDto>(
        `/pets/by-microchip/${encodeURIComponent(microchipNumber)}`,
    );
    if (!response.data) throw new Error("Pet not found");
    return PetModel.from(response.data);
}

import { api } from "@workspace/common";

import type { InCarePetDto } from "../../models/dtos/in-care-pet.dto";
import { InCarePetModel } from "../../models/in-care-pet.model";

export async function getInCarePets(): Promise<InCarePetModel[]> {
    const response = await api.get<InCarePetDto[]>("/hosting/in-care-pets");

    if (!response.data) {
        return [];
    }

    return response.data.map(InCarePetModel.from);
}

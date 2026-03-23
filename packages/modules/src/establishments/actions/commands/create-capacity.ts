import { api } from "@workspace/common";

import { CapacityDto } from "../../models/dtos/capacity.dto";
import { CapacityModel } from "../../models/capacity.model";
import type { CreateCapacityInput } from "../../validators/capacity.schema";

export async function createCapacity(
    establishmentId: string,
    input: CreateCapacityInput,
): Promise<CapacityModel> {
    const body = {
        animal_type_id: input.animalTypeId,
        max_capacity: input.maxCapacity,
        price_per_night: input.pricePerNight,
    };

    const response = await api.post<CapacityDto>(
        `/establishments/${establishmentId}/capacities`,
        body,
    );

    if (!response.data) {
        throw new Error("No data returned");
    }

    return CapacityModel.from(response.data);
}

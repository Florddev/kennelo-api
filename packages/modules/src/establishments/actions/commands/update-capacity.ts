import { api } from "@workspace/common";

import { CapacityDto } from "../../models/dtos/capacity.dto";
import { CapacityModel } from "../../models/capacity.model";
import type { UpdateCapacityInput } from "../../validators/capacity.schema";

export async function updateCapacity(
    establishmentId: string,
    capacityId: number,
    input: UpdateCapacityInput,
): Promise<CapacityModel> {
    const body: Record<string, unknown> = {};

    if (input.maxCapacity !== undefined) body.max_capacity = input.maxCapacity;
    if (input.pricePerNight !== undefined) body.price_per_night = input.pricePerNight;

    const response = await api.put<CapacityDto>(
        `/establishments/${establishmentId}/capacities/${capacityId}`,
        body,
    );

    if (!response.data) {
        throw new Error("No data returned");
    }

    return CapacityModel.from(response.data);
}

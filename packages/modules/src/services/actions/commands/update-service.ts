import { api } from "@workspace/common";

import { ServiceModel } from "../../models/service.model";
import type { ServiceDto } from "../../models/dtos/service.dto";
import type { UpdateServiceInput } from "../../validators/service.schema";

export async function updateService(
    activityId: string,
    serviceId: string,
    input: UpdateServiceInput,
): Promise<ServiceModel> {
    const body: Record<string, unknown> = {};

    if (input.name !== undefined) body.name = input.name;
    if (input.animalTypeId !== undefined) body.animal_type_id = input.animalTypeId;
    if (input.price !== undefined) body.price = input.price;
    if (input.description !== undefined) body.description = input.description || null;
    if (input.isIncluded !== undefined) body.is_included = input.isIncluded;

    const response = await api.put<ServiceDto>(
        `/activities/${activityId}/services/${serviceId}`,
        body,
    );

    if (!response.data) {
        throw new Error("Failed to update service");
    }

    return ServiceModel.from(response.data);
}

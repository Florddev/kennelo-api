import { api } from "@workspace/common";

import { ServiceModel } from "../../models/service.model";
import type { ServiceDto } from "../../models/dtos/service.dto";
import type { CreateServiceInput } from "../../validators/service.schema";

export async function createService(
    activityId: string,
    input: CreateServiceInput,
): Promise<ServiceModel> {
    const body: Record<string, unknown> = {
        name: input.name,
        animal_type_id: input.animalTypeId,
        price: input.price,
    };

    if (input.description !== undefined) body.description = input.description || null;
    if (input.isIncluded !== undefined) body.is_included = input.isIncluded;

    const response = await api.post<ServiceDto>(`/activities/${activityId}/services`, body);

    if (!response.data) {
        throw new Error("Failed to create service");
    }

    return ServiceModel.from(response.data);
}

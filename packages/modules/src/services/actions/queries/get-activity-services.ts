import { api } from "@workspace/common";

import { ServiceModel } from "../../models/service.model";
import type { ServiceDto } from "../../models/dtos/service.dto";

export async function getActivityServices(activityId: string): Promise<ServiceModel[]> {
    const response = await api.get<ServiceDto[]>(`/activities/${activityId}/services`);

    if (!response.data) {
        return [];
    }

    return response.data.map(ServiceModel.from);
}

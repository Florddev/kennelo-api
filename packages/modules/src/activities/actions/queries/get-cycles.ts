import { api } from "@workspace/common";

import { ActivityCycleModel } from "../../models/activity-cycle.model";
import type { ActivityCycleDto } from "../../models/dtos/activity-cycle.dto";

export async function getCycles(activityId: string): Promise<ActivityCycleModel[]> {
    const response = await api.get<ActivityCycleDto[]>(`/activities/${activityId}/cycles`);

    if (!response.data) {
        return [];
    }

    return response.data.map(ActivityCycleModel.from);
}

import { api } from "@workspace/common";

import { ActivityCycleModel } from "../../models/activity-cycle.model";
import type { ActivityCycleDto } from "../../models/dtos/activity-cycle.dto";

export async function getActivityPublicCycles(activityId: string): Promise<ActivityCycleModel[]> {
    const response = await api.get<ActivityCycleDto[]>(`/activities/${activityId}/public-cycles`);

    if (!response.data) {
        return [];
    }

    return response.data.map(ActivityCycleModel.from);
}

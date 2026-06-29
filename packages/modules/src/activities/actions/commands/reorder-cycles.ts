import { api } from "@workspace/common";

import { ActivityCycleModel } from "../../models/activity-cycle.model";
import type { ActivityCycleDto } from "../../models/dtos/activity-cycle.dto";

export async function reorderCycles(
    activityId: string,
    cycleIds: string[],
): Promise<ActivityCycleModel[]> {
    const response = await api.put<ActivityCycleDto[]>(`/activities/${activityId}/cycles/reorder`, {
        cycles: cycleIds,
    });

    if (!response.data) {
        return [];
    }

    return response.data.map(ActivityCycleModel.from);
}

import { api } from "@workspace/common";

import { ActivityCycleModel } from "../../models/activity-cycle.model";
import type { ActivityCycleDto } from "../../models/dtos/activity-cycle.dto";
import type { CreateCycleInput } from "../../validators/cycle.schema";

export async function createCycle(
    activityId: string,
    input: CreateCycleInput,
): Promise<ActivityCycleModel> {
    const body: Record<string, unknown> = {
        start_date: input.startDate || null,
        end_date: input.endDate || null,
    };

    if (input.priority !== undefined) body.priority = input.priority;
    if (input.isActive !== undefined) body.is_active = input.isActive;
    if (input.color !== undefined) body.color = input.color || null;

    const response = await api.post<ActivityCycleDto>(`/activities/${activityId}/cycles`, body);

    if (!response.data) {
        throw new Error("Failed to create cycle");
    }

    return ActivityCycleModel.from(response.data);
}

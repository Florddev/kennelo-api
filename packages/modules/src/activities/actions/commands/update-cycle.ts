import { api } from "@workspace/common";

import { ActivityCycleModel } from "../../models/activity-cycle.model";
import type { ActivityCycleDto } from "../../models/dtos/activity-cycle.dto";
import type { UpdateCycleInput } from "../../validators/cycle.schema";

export async function updateCycle(
    activityId: string,
    cycleId: string,
    input: UpdateCycleInput,
): Promise<ActivityCycleModel> {
    const body: Record<string, unknown> = {};

    if (input.startDate !== undefined) body.start_date = input.startDate || null;
    if (input.endDate !== undefined) body.end_date = input.endDate || null;
    if (input.priority !== undefined) body.priority = input.priority;
    if (input.isActive !== undefined) body.is_active = input.isActive;
    if (input.color !== undefined) body.color = input.color || null;

    const response = await api.put<ActivityCycleDto>(
        `/activities/${activityId}/cycles/${cycleId}`,
        body,
    );

    if (!response.data) {
        throw new Error("Failed to update cycle");
    }

    return ActivityCycleModel.from(response.data);
}

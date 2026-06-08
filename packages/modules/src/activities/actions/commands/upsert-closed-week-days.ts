import { api } from "@workspace/common";

import { ActivityCycleModel } from "../../models/activity-cycle.model";
import type { ActivityCycleDto } from "../../models/dtos/activity-cycle.dto";
import type { UpsertClosedWeekDaysInput } from "../../validators/closed-week-days.schema";

export async function upsertClosedWeekDays(
    activityId: string,
    cycleId: string,
    input: UpsertClosedWeekDaysInput,
): Promise<ActivityCycleModel> {
    const response = await api.put<ActivityCycleDto>(
        `/activities/${activityId}/cycles/${cycleId}/closed-week-days`,
        {
            sum_weekdays: input.sumWeekdays,
        },
    );

    if (!response.data) {
        throw new Error("Failed to update closed week days");
    }

    return ActivityCycleModel.from(response.data);
}

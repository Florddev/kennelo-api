import { api } from "@workspace/common";

import { ActivityCycleModel } from "../../models/activity-cycle.model";
import type { ActivityCycleDto } from "../../models/dtos/activity-cycle.dto";
import type { UpsertCycleSettingsInput } from "../../validators/cycle-settings.schema";

export async function upsertCycleSettings(
    activityId: string,
    cycleId: string,
    input: UpsertCycleSettingsInput,
): Promise<ActivityCycleModel> {
    const response = await api.put<ActivityCycleDto>(
        `/activities/${activityId}/cycles/${cycleId}/settings`,
        {
            settings: input.settings.map((setting) => ({
                animal_type_id: setting.animalTypeId,
                max_capacity: setting.maxCapacity,
                price: setting.price,
                sum_weekdays: setting.sumWeekdays,
            })),
        },
    );

    if (!response.data) {
        throw new Error("Failed to update cycle settings");
    }

    return ActivityCycleModel.from(response.data);
}

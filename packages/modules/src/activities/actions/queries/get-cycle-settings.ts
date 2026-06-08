import { api } from "@workspace/common";

import { ActivityCycleSettingModel } from "../../models/activity-cycle-setting.model";
import type { ActivityCycleSettingDto } from "../../models/dtos/activity-cycle-setting.dto";

export async function getActivityCycleSettings(
    activityId: string,
    date?: string,
): Promise<ActivityCycleSettingModel[]> {
    const params = date ? { date } : undefined;
    const response = await api.get<ActivityCycleSettingDto[]>(
        `/activities/${activityId}/cycle-settings`,
        params,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(ActivityCycleSettingModel.from);
}

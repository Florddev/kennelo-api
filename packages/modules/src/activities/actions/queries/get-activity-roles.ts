import { api } from "@workspace/common";

import { ActivityRoleModel } from "../../models/activity-role.model";
import type { ActivityRoleDto } from "../../models/dtos/activity-role.dto";

export async function getActivityRoles(activityId: string): Promise<ActivityRoleModel[]> {
    const response = await api.get<ActivityRoleDto[]>(`/activities/${activityId}/roles`);

    if (!response.data) {
        return [];
    }

    return response.data.map(ActivityRoleModel.from);
}

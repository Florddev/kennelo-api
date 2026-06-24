import { api } from "@workspace/common";

import { ActivityRoleModel } from "../../models/activity-role.model";
import type { ActivityRoleDto } from "../../models/dtos/activity-role.dto";
import type { ActivityRoleInput } from "../../validators/activity-role.schema";

export async function createActivityRole(
    activityId: string,
    input: ActivityRoleInput,
): Promise<ActivityRoleModel> {
    const response = await api.post<ActivityRoleDto>(`/activities/${activityId}/roles`, {
        name: input.name,
        permissions: input.permissions,
    });

    if (!response.data) {
        throw new Error("Failed to create role");
    }

    return ActivityRoleModel.from(response.data);
}

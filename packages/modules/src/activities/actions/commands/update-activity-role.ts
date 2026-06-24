import { api } from "@workspace/common";

import { ActivityRoleModel } from "../../models/activity-role.model";
import type { ActivityRoleDto } from "../../models/dtos/activity-role.dto";
import type { ActivityRoleInput } from "../../validators/activity-role.schema";

export async function updateActivityRole(
    activityId: string,
    roleId: string,
    input: ActivityRoleInput,
): Promise<ActivityRoleModel> {
    const response = await api.put<ActivityRoleDto>(`/activities/${activityId}/roles/${roleId}`, {
        name: input.name,
        permissions: input.permissions,
    });

    if (!response.data) {
        throw new Error("Failed to update role");
    }

    return ActivityRoleModel.from(response.data);
}

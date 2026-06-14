import { api } from "@workspace/common";

import { ActivityCollaboratorModel } from "../../models/activity-collaborator.model";
import type { ActivityCollaboratorDto } from "../../models/dtos/activity-collaborator.dto";
import type { AssignCollaboratorRoleInput } from "../../validators/assign-collaborator-role.schema";

export async function assignCollaboratorRole(
    activityId: string,
    userId: string,
    input: AssignCollaboratorRoleInput,
): Promise<ActivityCollaboratorModel> {
    const response = await api.put<ActivityCollaboratorDto>(
        `/activities/${activityId}/collaborators/${userId}/role`,
        { role_id: input.roleId },
    );

    if (!response.data) {
        throw new Error("Failed to assign role");
    }

    return ActivityCollaboratorModel.from(response.data);
}

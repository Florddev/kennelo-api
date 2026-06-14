import { api } from "@workspace/common";

import { ActivityCollaboratorModel } from "../../models/activity-collaborator.model";
import type { ActivityCollaboratorDto } from "../../models/dtos/activity-collaborator.dto";

export async function declineCollaboratorInvitation(
    activityId: string,
): Promise<ActivityCollaboratorModel | null> {
    const response = await api.put<ActivityCollaboratorDto>(
        `/activities/${activityId}/collaborators/decline`,
    );

    return response.data ? ActivityCollaboratorModel.from(response.data) : null;
}

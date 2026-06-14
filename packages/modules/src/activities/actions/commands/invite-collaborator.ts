import { api } from "@workspace/common";

import { ActivityCollaboratorModel } from "../../models/activity-collaborator.model";
import type { ActivityCollaboratorDto } from "../../models/dtos/activity-collaborator.dto";
import type { InviteCollaboratorInput } from "../../validators/invite-collaborator.schema";

export async function inviteCollaborator(
    activityId: string,
    input: InviteCollaboratorInput,
): Promise<ActivityCollaboratorModel> {
    const response = await api.post<ActivityCollaboratorDto>(
        `/activities/${activityId}/collaborators`,
        { email: input.email },
    );

    if (!response.data) {
        throw new Error("Failed to invite collaborator");
    }

    return ActivityCollaboratorModel.from(response.data);
}

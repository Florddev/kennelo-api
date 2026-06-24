import { api } from "@workspace/common";

import { ActivityCollaboratorModel } from "../../models/activity-collaborator.model";
import type { ActivityCollaboratorDto } from "../../models/dtos/activity-collaborator.dto";

export async function getMyCollaboratorInvitations(): Promise<ActivityCollaboratorModel[]> {
    const response = await api.get<ActivityCollaboratorDto[]>("/collaborator-invitations");

    if (!response.data) {
        return [];
    }

    return response.data.map(ActivityCollaboratorModel.from);
}

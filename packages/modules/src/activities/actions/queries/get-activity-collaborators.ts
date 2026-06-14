import { api } from "@workspace/common";

import { ActivityCollaboratorModel } from "../../models/activity-collaborator.model";
import type { ActivityCollaboratorDto } from "../../models/dtos/activity-collaborator.dto";

export async function getActivityCollaborators(
    activityId: string,
): Promise<ActivityCollaboratorModel[]> {
    const response = await api.get<ActivityCollaboratorDto[]>(
        `/activities/${activityId}/collaborators`,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(ActivityCollaboratorModel.from);
}

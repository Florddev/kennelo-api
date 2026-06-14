import { api } from "@workspace/common";

export async function removeCollaborator(activityId: string, userId: string): Promise<void> {
    await api.delete(`/activities/${activityId}/collaborators/${userId}`);
}

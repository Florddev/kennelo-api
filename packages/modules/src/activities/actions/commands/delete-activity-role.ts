import { api } from "@workspace/common";

export async function deleteActivityRole(activityId: string, roleId: string): Promise<void> {
    await api.delete(`/activities/${activityId}/roles/${roleId}`);
}

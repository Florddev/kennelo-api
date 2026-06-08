import { api } from "@workspace/common";

export async function deleteCycle(activityId: string, cycleId: string): Promise<void> {
    await api.delete(`/activities/${activityId}/cycles/${cycleId}`);
}

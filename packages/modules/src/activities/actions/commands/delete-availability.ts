import { api } from "@workspace/common";

export async function deleteAvailability(
    activityId: string,
    availabilityId: number,
): Promise<void> {
    await api.delete(`/activities/${activityId}/availabilities/${availabilityId}`);
}

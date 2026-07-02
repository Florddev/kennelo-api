import { api } from "@workspace/common";

export async function deleteService(activityId: string, serviceId: string): Promise<void> {
    await api.delete(`/activities/${activityId}/services/${serviceId}`);
}

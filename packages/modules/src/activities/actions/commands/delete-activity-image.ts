import { api } from "@workspace/common";

export async function deleteActivityImage(activityId: string, imageId: string): Promise<void> {
    await api.delete(`/activities/${activityId}/images/${imageId}`);
}

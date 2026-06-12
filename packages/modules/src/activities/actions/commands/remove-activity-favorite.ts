import { api } from "@workspace/common";

export async function removeActivityFavorite(activityId: string): Promise<void> {
    await api.delete(`/favorites/${activityId}`);
}

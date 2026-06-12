import { api } from "@workspace/common";

export async function addActivityFavorite(activityId: string): Promise<void> {
    await api.post("/favorites", { activity_id: activityId });
}

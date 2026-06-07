import { api } from "@workspace/common";

export async function deleteActivity(id: string): Promise<void> {
    await api.delete(`/activities/${id}`);
}

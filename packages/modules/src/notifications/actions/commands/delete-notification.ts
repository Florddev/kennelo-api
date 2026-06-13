import { api } from "@workspace/common";

export async function deleteNotification(id: string): Promise<void> {
    await api.delete(`/notifications/${id}`);
}

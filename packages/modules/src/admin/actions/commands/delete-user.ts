import { api } from "@workspace/common";

export async function deleteUser(userId: string): Promise<void> {
    await api.delete<void>(`/admin/users/${userId}`);
}

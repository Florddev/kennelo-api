import { api } from "@workspace/common";

export async function forcePasswordReset(userId: string): Promise<void> {
    await api.post(`/admin/users/${userId}/force-password-reset`, {});
}

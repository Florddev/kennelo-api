import { api } from "@workspace/common";

export async function deleteProspect(prospectId: string): Promise<void> {
    await api.delete<void>(`/admin/prospects/${prospectId}`);
}

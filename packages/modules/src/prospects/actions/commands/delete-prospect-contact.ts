import { api } from "@workspace/common";

export async function deleteProspectContact(prospectId: string, contactId: string): Promise<void> {
    await api.delete<void>(`/admin/prospects/${prospectId}/contacts/${contactId}`);
}

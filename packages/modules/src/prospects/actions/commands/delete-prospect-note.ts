import { api } from "@workspace/common";

export async function deleteProspectNote(prospectId: string, noteId: string): Promise<void> {
    await api.delete<void>(`/admin/prospects/${prospectId}/notes/${noteId}`);
}

import { api } from "@workspace/common";

import type { ProspectNoteDto } from "../../models/dtos/prospect-note.dto";
import { ProspectNoteModel } from "../../models/prospect-note.model";

export async function getProspectNotes(prospectId: string): Promise<ProspectNoteModel[]> {
    const response = await api.get<ProspectNoteDto[]>(`/admin/prospects/${prospectId}/notes`);

    if (!response.data) return [];
    return response.data.map(ProspectNoteModel.from);
}

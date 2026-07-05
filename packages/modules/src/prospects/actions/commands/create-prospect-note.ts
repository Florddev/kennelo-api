import { api } from "@workspace/common";

import type { ProspectNoteDto } from "../../models/dtos/prospect-note.dto";
import { ProspectNoteModel } from "../../models/prospect-note.model";
import type { ProspectNoteInput } from "../../validators/prospect-note.schema";

export async function createProspectNote(
    prospectId: string,
    input: ProspectNoteInput,
): Promise<ProspectNoteModel | null> {
    const response = await api.post<ProspectNoteDto>(`/admin/prospects/${prospectId}/notes`, {
        body: input.body,
    });

    if (!response.data) return null;
    return ProspectNoteModel.from(response.data);
}

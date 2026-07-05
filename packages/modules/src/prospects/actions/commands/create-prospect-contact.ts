import { api } from "@workspace/common";

import type { ProspectContactDto } from "../../models/dtos/prospect-contact.dto";
import { ProspectContactModel } from "../../models/prospect-contact.model";
import type { ProspectContactInput } from "../../validators/prospect-contact.schema";

export async function createProspectContact(
    prospectId: string,
    input: ProspectContactInput,
): Promise<ProspectContactModel | null> {
    const response = await api.post<ProspectContactDto>(`/admin/prospects/${prospectId}/contacts`, {
        type: input.type,
        contacted_at: input.contactedAt ?? undefined,
        outcome: input.outcome ?? undefined,
        notes: input.notes ?? undefined,
    });

    if (!response.data) return null;
    return ProspectContactModel.from(response.data);
}

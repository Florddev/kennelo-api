import { api } from "@workspace/common";

import type { ProspectContactDto } from "../../models/dtos/prospect-contact.dto";
import { ProspectContactModel } from "../../models/prospect-contact.model";

export async function getProspectContacts(prospectId: string): Promise<ProspectContactModel[]> {
    const response = await api.get<ProspectContactDto[]>(`/admin/prospects/${prospectId}/contacts`);

    if (!response.data) return [];
    return response.data.map(ProspectContactModel.from);
}

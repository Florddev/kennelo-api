import { api } from "@workspace/common";

import type { ProspectDto } from "../../models/dtos/prospect.dto";
import { ProspectModel } from "../../models/prospect.model";

export async function assignProspect(
    prospectId: string,
    assignedTo: string | null,
): Promise<ProspectModel | null> {
    const response = await api.put<ProspectDto>(`/admin/prospects/${prospectId}/assign`, {
        assigned_to: assignedTo,
    });

    if (!response.data) return null;
    return ProspectModel.from(response.data);
}

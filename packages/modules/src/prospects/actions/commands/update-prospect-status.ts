import { api } from "@workspace/common";

import type { ProspectDto } from "../../models/dtos/prospect.dto";
import { ProspectModel } from "../../models/prospect.model";
import type { ProspectStatusValue } from "../../types/prospect-status.type";

export async function updateProspectStatus(
    prospectId: string,
    status: ProspectStatusValue,
): Promise<ProspectModel | null> {
    const response = await api.put<ProspectDto>(`/admin/prospects/${prospectId}/status`, {
        status,
    });

    if (!response.data) return null;
    return ProspectModel.from(response.data);
}

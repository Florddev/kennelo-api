import { api } from "@workspace/common";

import type { ProspectDto } from "../../models/dtos/prospect.dto";
import { ProspectModel } from "../../models/prospect.model";

export async function reconcileProspect(prospectId: string): Promise<ProspectModel | null> {
    const response = await api.post<ProspectDto>(`/admin/prospects/${prospectId}/reconcile`);

    if (!response.data) return null;
    return ProspectModel.from(response.data);
}

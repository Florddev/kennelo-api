import { api } from "@workspace/common";

import type { ProspectDto } from "../../models/dtos/prospect.dto";
import { ProspectModel } from "../../models/prospect.model";

export async function getProspect(prospectId: string): Promise<ProspectModel | null> {
    const response = await api.get<ProspectDto>(`/admin/prospects/${prospectId}`);

    if (!response.data) return null;
    return ProspectModel.from(response.data);
}

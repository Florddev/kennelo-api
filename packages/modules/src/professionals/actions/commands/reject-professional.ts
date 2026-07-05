import { api } from "@workspace/common";

import type { ProfessionalDto } from "../../models/dtos/professional.dto";
import { ProfessionalModel } from "../../models/professional.model";

export async function rejectProfessional(
    activityId: string,
    reason: string,
): Promise<ProfessionalModel | null> {
    const response = await api.post<ProfessionalDto>(`/admin/activities/${activityId}/reject`, {
        reason,
    });

    if (!response.data) return null;
    return ProfessionalModel.from(response.data);
}

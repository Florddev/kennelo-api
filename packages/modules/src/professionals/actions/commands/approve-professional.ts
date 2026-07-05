import { api } from "@workspace/common";

import type { ProfessionalDto } from "../../models/dtos/professional.dto";
import { ProfessionalModel } from "../../models/professional.model";

export async function approveProfessional(activityId: string): Promise<ProfessionalModel | null> {
    const response = await api.post<ProfessionalDto>(`/admin/activities/${activityId}/approve`);

    if (!response.data) return null;
    return ProfessionalModel.from(response.data);
}

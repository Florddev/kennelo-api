import { api } from "@workspace/common";

import type { ProfessionalDto } from "../../models/dtos/professional.dto";
import { ProfessionalModel } from "../../models/professional.model";

export async function unlinkActivityGoogle(activityId: string): Promise<ProfessionalModel | null> {
    const response = await api.delete<ProfessionalDto>(`/admin/activities/${activityId}/google`);

    if (!response.data) return null;
    return ProfessionalModel.from(response.data);
}

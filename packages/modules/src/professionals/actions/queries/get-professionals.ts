import { api } from "@workspace/common";

import type { ProfessionalDto } from "../../models/dtos/professional.dto";
import { ProfessionalModel } from "../../models/professional.model";
import type { ActivityStatusValue } from "../../types/activity-status.type";

export async function getProfessionals(input?: {
    search?: string;
    status?: ActivityStatusValue;
    professional?: boolean;
    department?: string;
    perPage?: number;
}): Promise<ProfessionalModel[]> {
    const params: Record<string, string | number | boolean> = {};

    if (input?.search) params.search = input.search;
    if (input?.status) params.status = input.status;
    if (input?.professional != null) params.professional = input.professional;
    if (input?.department) params.department = input.department;
    if (input?.perPage != null) params.per_page = input.perPage;

    const response = await api.get<ProfessionalDto[]>(
        "/admin/activities",
        Object.keys(params).length > 0 ? params : undefined,
    );

    if (!response.data) return [];
    return response.data.map(ProfessionalModel.from);
}

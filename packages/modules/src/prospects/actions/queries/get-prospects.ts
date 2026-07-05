import { api } from "@workspace/common";

import type { ProspectDto } from "../../models/dtos/prospect.dto";
import { ProspectModel } from "../../models/prospect.model";
import type { ProspectStatusValue } from "../../types/prospect-status.type";

export type ProspectSortBy = "name" | "created_at" | "google_rating" | "status";
export type ProspectSortDir = "asc" | "desc";

export async function getProspects(input?: {
    search?: string;
    status?: ProspectStatusValue;
    department?: string;
    region?: string;
    assignedTo?: string;
    registered?: boolean;
    minRating?: number;
    sortBy?: ProspectSortBy;
    sortDirection?: ProspectSortDir;
    perPage?: number;
}): Promise<ProspectModel[]> {
    const params: Record<string, string | number | boolean> = {};

    if (input?.search) params.search = input.search;
    if (input?.status) params.status = input.status;
    if (input?.department) params.department = input.department;
    if (input?.region) params.region = input.region;
    if (input?.assignedTo) params.assigned_to = input.assignedTo;
    if (input?.registered != null) params.registered = input.registered;
    if (input?.minRating != null) params.min_rating = input.minRating;
    if (input?.sortBy) params.sort_by = input.sortBy;
    if (input?.sortDirection) params.sort_direction = input.sortDirection;
    if (input?.perPage != null) params.per_page = input.perPage;

    const response = await api.get<ProspectDto[]>(
        "/admin/prospects",
        Object.keys(params).length > 0 ? params : undefined,
    );

    if (!response.data) return [];
    return response.data.map(ProspectModel.from);
}

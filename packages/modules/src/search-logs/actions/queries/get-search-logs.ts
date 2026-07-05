import { api } from "@workspace/common";

import type { SearchLogDto } from "../../models/dtos/search-log.dto";
import { SearchLogModel } from "../../models/search-log.model";

export async function getSearchLogs(input?: {
    search?: string;
    department?: string;
    from?: string;
    perPage?: number;
}): Promise<SearchLogModel[]> {
    const params: Record<string, string | number | boolean> = {};

    if (input?.search) params.search = input.search;
    if (input?.department) params.department = input.department;
    if (input?.from) params.from = input.from;
    if (input?.perPage != null) params.per_page = input.perPage;

    const response = await api.get<SearchLogDto[]>(
        "/admin/search-logs",
        Object.keys(params).length > 0 ? params : undefined,
    );

    if (!response.data) return [];
    return response.data.map(SearchLogModel.from);
}

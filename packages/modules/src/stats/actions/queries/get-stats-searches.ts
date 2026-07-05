import { api } from "@workspace/common";

import type { StatsSearchesDto } from "../../models/dtos/stats.dto";

export async function getStatsSearches(): Promise<StatsSearchesDto | null> {
    const response = await api.get<StatsSearchesDto>("/admin/stats/searches");

    return response.data;
}

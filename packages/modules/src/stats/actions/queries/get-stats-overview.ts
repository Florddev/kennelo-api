import { api } from "@workspace/common";

import type { StatsOverviewDto } from "../../models/dtos/stats.dto";

export async function getStatsOverview(): Promise<StatsOverviewDto | null> {
    const response = await api.get<StatsOverviewDto>("/admin/stats/overview");

    return response.data;
}

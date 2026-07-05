import { api } from "@workspace/common";

import type { StatsFinanceDto } from "../../models/dtos/stats-finance.dto";

export async function getStatsFinance(): Promise<StatsFinanceDto | null> {
    const response = await api.get<StatsFinanceDto>("/admin/stats/finance");

    return response.data;
}

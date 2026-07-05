import { api } from "@workspace/common";

import type { StatsBusinessDto } from "../../models/dtos/stats-business.dto";

export async function getStatsBusiness(): Promise<StatsBusinessDto | null> {
    const response = await api.get<StatsBusinessDto>("/admin/stats/business");

    return response.data;
}

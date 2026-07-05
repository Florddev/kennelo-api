import { api } from "@workspace/common";

import type { StatsCommunityDto } from "../../models/dtos/stats-community.dto";

export async function getStatsCommunity(): Promise<StatsCommunityDto | null> {
    const response = await api.get<StatsCommunityDto>("/admin/stats/community");

    return response.data;
}

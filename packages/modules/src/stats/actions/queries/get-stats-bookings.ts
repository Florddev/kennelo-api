import { api } from "@workspace/common";

import type { StatsBookingsDto } from "../../models/dtos/stats-bookings.dto";

export async function getStatsBookings(): Promise<StatsBookingsDto | null> {
    const response = await api.get<StatsBookingsDto>("/admin/stats/bookings");

    return response.data;
}

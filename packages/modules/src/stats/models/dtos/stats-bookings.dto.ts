import type { GrowthMetricDto } from "./stats-business.dto";

export type StatsBookingsDto = {
    total: number;
    by_status: Record<string, number>;
    cancellation_rate: number;
    payment_conversion_rate: number;
    avg_stay_nights: number | null;
    monthly: GrowthMetricDto;
};

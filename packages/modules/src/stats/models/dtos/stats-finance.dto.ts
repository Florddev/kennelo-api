import type { GrowthMetricDto } from "./stats-business.dto";

export type StatsFinanceDto = {
    gmv: number;
    kennelo_revenue: number;
    take_rate: number;
    net_to_pros: number;
    refunds: number;
    refund_rate: number;
    avg_basket: number;
    paid_bookings: number;
    gmv_growth: GrowthMetricDto;
};

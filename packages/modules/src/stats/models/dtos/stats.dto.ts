export type StatsOverviewDto = {
    activities: {
        total: number;
        approved: number;
        pending: number;
        rejected: number;
        professionals: number;
    };
    prospects: {
        total: number;
        registered: number;
        by_status: Record<string, number>;
        from_prospection: number;
    };
    searches: {
        total: number;
        last_30_days: number;
    };
};

export type StatsSearchesDto = {
    by_department: { department: string; total: number }[];
    monthly: { month: string; total: number }[];
    total: number;
    zero_result_count: number;
    zero_result_rate: number;
    top_filters: { filter: string; total: number }[];
};

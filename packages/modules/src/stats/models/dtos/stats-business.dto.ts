export type ProspectionFunnelDto = {
    identified: number;
    contacted: number;
    registered: number;
    refused: number;
    contact_rate: number;
    conversion_rate: number;
    overall_conversion_rate: number;
};

export type ValidationStatsDto = {
    approved: number;
    rejected: number;
    pending: number;
    approval_rate: number;
    avg_processing_days: number | null;
};

export type MarketCoverageDto = {
    department: string;
    searches: number;
    professionals: number;
    opportunity_score: number;
};

export type GrowthMetricDto = {
    series: { month: string; total: number }[];
    current: number;
    previous: number;
    variation: number | null;
};

export type TeamMemberPerformanceDto = {
    member: string;
    contacts: number;
    conversions: number;
    imported: number;
};

export type StatsBusinessDto = {
    prospection_funnel: ProspectionFunnelDto;
    validation: ValidationStatsDto;
    market_coverage: MarketCoverageDto[];
    growth: {
        searches: GrowthMetricDto;
        prospects: GrowthMetricDto;
        registrations: GrowthMetricDto;
    };
    team_performance: TeamMemberPerformanceDto[];
    contact_type_mix: Record<string, number>;
};

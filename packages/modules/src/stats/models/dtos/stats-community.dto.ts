import type { GrowthMetricDto } from "./stats-business.dto";

export type StatsCommunityDto = {
    user_growth: GrowthMetricDto;
    roles_split: {
        owners: number;
        pros: number;
        admins: number;
    };
    kyc_rate: number;
    email_verified_rate: number;
    active_users: {
        dau: number;
        wau: number;
        mau: number;
    };
    avg_pro_rating: number | null;
    rating_distribution: Record<string, number>;
    review_response_rate: number;
    messages_last_30_days: number;
    active_conversations: number;
};

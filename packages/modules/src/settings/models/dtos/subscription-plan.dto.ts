export type SubscriptionPlanDto = {
    id: string;
    slug: string;
    name: string;
    description: string | null;
    price_monthly: string;
    price_yearly: string | null;
    currency: string;
    commission_rate: string;
    features: Record<string, unknown> | null;
    limits: {
        max_activities?: number | null;
        max_cycles_per_activity?: number | null;
        max_photos?: number | null;
    } | null;
    is_active: boolean;
};

import type { Plan } from "../../types/plan.type";
import type { SubscriptionStatus } from "../../types/subscription-status.type";
import type { SubscriptionPlanDto } from "./subscription-plan.dto";

export type SubscriptionDto = {
    id?: string | null;
    user_id?: string | null;
    plan: Plan;
    status: SubscriptionStatus | null;
    is_effective: boolean;
    current_period_start?: string | null;
    current_period_end?: string | null;
    trial_ends_at?: string | null;
    canceled_at?: string | null;
    ends_at?: string | null;
    plan_details?: SubscriptionPlanDto | null;
    created_at?: string | null;
};

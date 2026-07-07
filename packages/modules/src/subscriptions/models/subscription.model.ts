import type { Plan } from "../types/plan.type";
import type { SubscriptionStatus } from "../types/subscription-status.type";
import type { SubscriptionDto } from "./dtos/subscription.dto";
import { SubscriptionPlanModel } from "./subscription-plan.model";

export class SubscriptionModel {
    private constructor(
        public readonly id: string | null,
        public readonly userId: string | null,
        public readonly plan: Plan,
        public readonly status: SubscriptionStatus | null,
        public readonly isEffective: boolean,
        public readonly currentPeriodStart: string | null,
        public readonly currentPeriodEnd: string | null,
        public readonly trialEndsAt: string | null,
        public readonly canceledAt: string | null,
        public readonly endsAt: string | null,
        public readonly planDetails: SubscriptionPlanModel | null,
        public readonly createdAt: string | null,
    ) {}

    static from(dto: SubscriptionDto): SubscriptionModel {
        return new SubscriptionModel(
            dto.id ?? null,
            dto.user_id ?? null,
            dto.plan,
            dto.status ?? null,
            dto.is_effective,
            dto.current_period_start ?? null,
            dto.current_period_end ?? null,
            dto.trial_ends_at ?? null,
            dto.canceled_at ?? null,
            dto.ends_at ?? null,
            dto.plan_details ? SubscriptionPlanModel.from(dto.plan_details) : null,
            dto.created_at ?? null,
        );
    }

    isFree(): boolean {
        return this.plan === "free";
    }

    isActive(): boolean {
        return this.status === "active";
    }

    isPastDue(): boolean {
        return this.status === "past_due" || this.status === "unpaid";
    }

    isCanceling(): boolean {
        return this.canceledAt !== null && this.isEffective;
    }
}

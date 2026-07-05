import type { Plan } from "../types/plan.type";
import type { SubscriptionPlanDto, SubscriptionPlanLimitsDto } from "./dtos/subscription-plan.dto";

export class SubscriptionPlanModel {
    private constructor(
        public readonly id: string,
        public readonly slug: Plan,
        public readonly name: string,
        public readonly description: string | null,
        public readonly priceMonthly: string,
        public readonly priceYearly: string | null,
        public readonly currency: string,
        public readonly commissionRate: string,
        public readonly features: Record<string, unknown> | null,
        public readonly limits: SubscriptionPlanLimitsDto | null,
        public readonly isActive: boolean,
    ) {}

    static from(dto: SubscriptionPlanDto): SubscriptionPlanModel {
        return new SubscriptionPlanModel(
            dto.id,
            dto.slug,
            dto.name,
            dto.description ?? null,
            dto.price_monthly,
            dto.price_yearly ?? null,
            dto.currency,
            dto.commission_rate,
            dto.features ?? null,
            dto.limits ?? null,
            dto.is_active,
        );
    }

    isFree(): boolean {
        return this.slug === "free";
    }

    commissionPercent(): number {
        return Math.round(Number(this.commissionRate) * 100);
    }

    limit(key: keyof SubscriptionPlanLimitsDto): number | null {
        const value = this.limits?.[key];
        return value === undefined || value === null ? null : value;
    }

    isUnlimited(key: keyof SubscriptionPlanLimitsDto): boolean {
        const value = this.limit(key);
        return value === null || value < 0;
    }
}

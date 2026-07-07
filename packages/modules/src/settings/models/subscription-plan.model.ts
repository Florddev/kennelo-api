import type { SubscriptionPlanDto } from "./dtos/subscription-plan.dto";

export type SubscriptionPlanLimits = {
    maxActivities: number | null;
    maxCyclesPerActivity: number | null;
    maxPhotos: number | null;
};

export class SubscriptionPlanModel {
    private constructor(
        public readonly id: string,
        public readonly slug: string,
        public readonly name: string,
        public readonly description: string | null,
        public readonly priceMonthly: string,
        public readonly priceYearly: string | null,
        public readonly currency: string,
        public readonly commissionRate: string,
        public readonly limits: SubscriptionPlanLimits,
        public readonly isActive: boolean,
    ) {}

    static from(dto: SubscriptionPlanDto): SubscriptionPlanModel {
        return new SubscriptionPlanModel(
            dto.id,
            dto.slug,
            dto.name,
            dto.description,
            dto.price_monthly,
            dto.price_yearly,
            dto.currency,
            dto.commission_rate,
            {
                maxActivities: dto.limits?.max_activities ?? null,
                maxCyclesPerActivity: dto.limits?.max_cycles_per_activity ?? null,
                maxPhotos: dto.limits?.max_photos ?? null,
            },
            dto.is_active,
        );
    }
}

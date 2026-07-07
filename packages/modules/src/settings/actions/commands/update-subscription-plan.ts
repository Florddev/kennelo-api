import { api } from "@workspace/common";
import type { SubscriptionPlanDto } from "../../models/dtos/subscription-plan.dto";
import { SubscriptionPlanModel } from "../../models/subscription-plan.model";
import type { UpdateSubscriptionPlanInput } from "../../validators/subscription-plan.schema";

export async function updateSubscriptionPlan(
    planId: string,
    input: UpdateSubscriptionPlanInput,
): Promise<SubscriptionPlanModel | null> {
    const response = await api.put<SubscriptionPlanDto>(`/admin/subscription-plans/${planId}`, {
        name: input.name,
        description: input.description ?? null,
        price_monthly: String(input.priceMonthly),
        commission_rate: String(input.commissionRate),
        limits: {
            max_activities: input.maxActivities,
            max_cycles_per_activity: input.maxCyclesPerActivity,
            max_photos: input.maxPhotos,
        },
        is_active: input.isActive,
    });

    if (!response.data) {
        return null;
    }

    return SubscriptionPlanModel.from(response.data);
}

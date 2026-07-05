import { api } from "@workspace/common";
import type { SubscriptionPlanDto } from "../../models/dtos/subscription-plan.dto";
import { SubscriptionPlanModel } from "../../models/subscription-plan.model";

export async function getPlans(): Promise<SubscriptionPlanModel[]> {
    const response = await api.get<SubscriptionPlanDto[]>("/plans");

    if (!response.data) {
        return [];
    }

    return response.data.map(SubscriptionPlanModel.from);
}

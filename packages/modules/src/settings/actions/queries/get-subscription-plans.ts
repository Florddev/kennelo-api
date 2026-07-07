import { api } from "@workspace/common";
import type { SubscriptionPlanDto } from "../../models/dtos/subscription-plan.dto";
import { SubscriptionPlanModel } from "../../models/subscription-plan.model";

export async function getSubscriptionPlans(): Promise<SubscriptionPlanModel[]> {
    const response = await api.get<SubscriptionPlanDto[]>("/admin/subscription-plans");

    if (!response.data) {
        return [];
    }

    return response.data.map(SubscriptionPlanModel.from);
}

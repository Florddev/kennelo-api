import { api } from "@workspace/common";
import type { SubscriptionDto } from "../../models/dtos/subscription.dto";
import { SubscriptionModel } from "../../models/subscription.model";

export async function cancelSubscription(): Promise<SubscriptionModel> {
    const response = await api.delete<SubscriptionDto>("/me/subscription");

    if (!response.data) {
        throw new Error("Failed to cancel subscription");
    }

    return SubscriptionModel.from(response.data);
}

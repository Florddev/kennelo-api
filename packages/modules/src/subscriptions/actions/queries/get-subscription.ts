import { api } from "@workspace/common";
import type { SubscriptionDto } from "../../models/dtos/subscription.dto";
import { SubscriptionModel } from "../../models/subscription.model";

export async function getSubscription(): Promise<SubscriptionModel> {
    const response = await api.get<SubscriptionDto>("/me/subscription");

    if (!response.data) {
        throw new Error("No data returned");
    }

    return SubscriptionModel.from(response.data);
}

import { api } from "@workspace/common";
import type { SubscriptionDto } from "../../models/dtos/subscription.dto";
import { SubscriptionModel } from "../../models/subscription.model";

export async function getActivitySubscription(activityId: string): Promise<SubscriptionModel> {
    const response = await api.get<SubscriptionDto>(`/activities/${activityId}/subscription`);

    if (!response.data) {
        throw new Error("No data returned");
    }

    return SubscriptionModel.from(response.data);
}

import { api } from "@workspace/common";
import type { CheckoutSessionDto } from "../../models/dtos/checkout-session.dto";
import type { Plan } from "../../types/plan.type";

export async function startSubscriptionCheckout(activityId: string, plan: Plan): Promise<string> {
    const response = await api.post<CheckoutSessionDto>(
        `/activities/${activityId}/subscription/checkout`,
        {
            plan_slug: plan,
        },
    );

    if (!response.data?.checkout_url) {
        throw new Error("Failed to start checkout");
    }

    return response.data.checkout_url;
}

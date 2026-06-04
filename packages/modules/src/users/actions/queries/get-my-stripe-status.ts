import { api } from "@workspace/common";

type StripeStatusDto = {
    stripe_account_id: string | null;
    onboarding_completed: boolean;
    charges_enabled: boolean;
    payouts_enabled: boolean;
};

export type StripeStatus = {
    stripeAccountId: string | null;
    onboardingCompleted: boolean;
    chargesEnabled: boolean;
    payoutsEnabled: boolean;
};

export async function getMyStripeStatus(): Promise<StripeStatus> {
    const response = await api.get<StripeStatusDto>("/users/me/stripe/status");
    if (!response.data) {
        throw new Error("Failed to fetch Stripe status");
    }
    return {
        stripeAccountId: response.data.stripe_account_id,
        onboardingCompleted: response.data.onboarding_completed,
        chargesEnabled: response.data.charges_enabled,
        payoutsEnabled: response.data.payouts_enabled,
    };
}

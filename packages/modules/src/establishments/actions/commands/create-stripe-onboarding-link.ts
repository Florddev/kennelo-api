import { api } from "@workspace/common";

type OnboardingLinkResponse = {
    url: string;
};

export async function createStripeOnboardingLink(establishmentId: string): Promise<string> {
    const response = await api.post<OnboardingLinkResponse>(
        `/establishments/${establishmentId}/stripe/onboarding-link`,
        {},
    );

    if (!response.data?.url) {
        throw new Error("Failed to create Stripe onboarding link");
    }

    return response.data.url;
}

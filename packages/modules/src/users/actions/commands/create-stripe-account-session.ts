import { api } from "@workspace/common";

type AccountSessionDto = {
    client_secret: string;
};

export async function createStripeAccountSession(): Promise<{ clientSecret: string }> {
    const response = await api.post<AccountSessionDto>("/users/me/stripe/account-session", {});
    if (!response.data?.client_secret) {
        throw new Error("Failed to create Stripe account session");
    }
    return { clientSecret: response.data.client_secret };
}

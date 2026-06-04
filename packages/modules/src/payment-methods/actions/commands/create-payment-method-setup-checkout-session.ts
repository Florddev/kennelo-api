import { api } from "@workspace/common";

type SetupCheckoutSessionDto = {
    client_secret: string;
    session_id: string;
};

export async function createPaymentMethodSetupCheckoutSession(): Promise<{
    clientSecret: string;
    sessionId: string;
}> {
    const response = await api.post<SetupCheckoutSessionDto>(
        "/me/payment-methods/setup-checkout-session",
        {},
    );
    if (!response.data?.client_secret) {
        throw new Error("Failed to create setup checkout session");
    }
    return {
        clientSecret: response.data.client_secret,
        sessionId: response.data.session_id,
    };
}

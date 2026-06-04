import { loadConnectAndInitialize, type StripeConnectInstance } from "@stripe/connect-js";

export function initStripeConnect(fetchClientSecret: () => Promise<string>): StripeConnectInstance {
    const publishableKey = process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY;
    if (!publishableKey) {
        throw new Error("Missing NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY environment variable.");
    }
    return loadConnectAndInitialize({
        publishableKey,
        fetchClientSecret,
        appearance: { variables: { colorPrimary: "#16a34a" } },
    });
}

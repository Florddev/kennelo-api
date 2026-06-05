import { loadStripe, type Stripe } from "@stripe/stripe-js";

let stripePromise: Promise<Stripe | null> | null = null;

export function getStripe(): Promise<Stripe | null> {
    if (stripePromise === null) {
        const publishableKey = process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY;
        if (!publishableKey) {
            throw new Error("Missing NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY environment variable.");
        }
        stripePromise = loadStripe(publishableKey);
    }
    return stripePromise;
}

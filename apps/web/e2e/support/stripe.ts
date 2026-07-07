import { existsSync, readFileSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const ROOT_DIR = dirname(fileURLToPath(import.meta.url));
const API_ENV = resolve(ROOT_DIR, "..", "..", "..", "..", "api", ".env");

function readApiEnv(key: string): string | undefined {
    if (process.env[key]) return process.env[key];
    if (!existsSync(API_ENV)) return undefined;
    for (const line of readFileSync(API_ENV, "utf8").split(/\r?\n/)) {
        const match = line.match(new RegExp(`^\\s*${key}\\s*=\\s*(.*)$`));
        if (match && match[1] !== undefined) return match[1].trim().replace(/^["']|["']$/g, "");
    }
    return undefined;
}

export const STRIPE_SECRET = readApiEnv("STRIPE_SECRET");
export const STRIPE_PUBLISHABLE = process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY;
export const STRIPE_ENABLED = Boolean(STRIPE_SECRET && STRIPE_PUBLISHABLE);

export const TEST_PAYMENT_METHOD = "pm_card_visa";

export async function confirmSetupIntent(
    setupIntentId: string,
    paymentMethod = TEST_PAYMENT_METHOD,
): Promise<void> {
    if (!STRIPE_SECRET) throw new Error("STRIPE_SECRET is not configured");
    const body = new URLSearchParams({ payment_method: paymentMethod });
    const response = await fetch(
        `https://api.stripe.com/v1/setup_intents/${setupIntentId}/confirm`,
        {
            method: "POST",
            headers: {
                Authorization: `Bearer ${STRIPE_SECRET}`,
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body,
        },
    );
    if (!response.ok) {
        throw new Error(`Stripe confirm failed: ${response.status} ${await response.text()}`);
    }
    const json = (await response.json()) as { status?: string };
    if (json.status !== "succeeded") {
        throw new Error(`SetupIntent not succeeded: ${json.status}`);
    }
}

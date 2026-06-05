import { api } from "@workspace/common";
import type { SetupIntentDto } from "../../models/dtos/setup-intent.dto";

export async function createPaymentMethodSetupIntent(): Promise<{
    clientSecret: string;
    setupIntentId: string;
}> {
    const response = await api.post<SetupIntentDto>("/me/payment-methods/setup-intent", {});
    if (!response.data?.client_secret) {
        throw new Error("Failed to create setup intent");
    }
    return {
        clientSecret: response.data.client_secret,
        setupIntentId: response.data.setup_intent_id,
    };
}

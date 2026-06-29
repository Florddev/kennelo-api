import { api } from "@workspace/common";

export async function resendVerification(): Promise<void> {
    const response = await api.post("/email/verification-notification");

    if (response.status !== 200) {
        throw new Error("Failed to resend verification email");
    }
}

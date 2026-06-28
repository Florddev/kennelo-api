import { api } from "@workspace/common";
import { ForgotPasswordInput } from "../../validators/forgot-password.schema";

export async function forgotPassword(input: ForgotPasswordInput): Promise<void> {
    const response = await api.post("/forgot-password", {
        email: input.email,
    });

    if (response.status !== 200) {
        throw new Error("Failed to send password reset link");
    }
}

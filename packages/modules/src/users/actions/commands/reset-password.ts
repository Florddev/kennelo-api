import { api } from "@workspace/common";
import { ResetPasswordInput } from "../../validators/reset-password.schema";

export async function resetPassword(input: ResetPasswordInput): Promise<void> {
    const response = await api.post("/reset-password", {
        token: input.token,
        email: input.email,
        password: input.password,
        password_confirmation: input.passwordConfirmation,
    });

    if (response.status !== 200) {
        throw new Error("Failed to reset password");
    }
}

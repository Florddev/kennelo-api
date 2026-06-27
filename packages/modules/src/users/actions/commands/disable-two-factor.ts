import { api } from "@workspace/common";
import { DisableTwoFactorInput } from "../../validators/two-factor.schema";

export async function disableTwoFactor(input: DisableTwoFactorInput): Promise<void> {
    const response = await api.delete("/user/two-factor", {
        body: JSON.stringify({ password: input.password }),
    });

    if (response.status !== 204) {
        throw new Error("Failed to disable two-factor authentication");
    }
}

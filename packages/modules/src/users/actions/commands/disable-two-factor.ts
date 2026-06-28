import { api } from "@workspace/common";
import { TwoFactorStepUp, twoFactorStepUpBody } from "../../models/dtos/two-factor.dto";

export async function disableTwoFactor(stepUp: TwoFactorStepUp): Promise<void> {
    const response = await api.delete("/user/two-factor", {
        body: JSON.stringify(twoFactorStepUpBody(stepUp)),
    });

    if (response.status !== 204) {
        throw new Error("Failed to disable two-factor authentication");
    }
}

import { api } from "@workspace/common";
import {
    RecoveryCodesDto,
    TwoFactorStepUp,
    twoFactorStepUpBody,
} from "../../models/dtos/two-factor.dto";

export async function regenerateRecoveryCodes(stepUp: TwoFactorStepUp): Promise<string[]> {
    const response = await api.post<RecoveryCodesDto>(
        "/user/two-factor/recovery-codes",
        twoFactorStepUpBody(stepUp),
    );

    if (response.status !== 200) {
        throw new Error("Failed to regenerate recovery codes");
    }

    return response.data?.recovery_codes ?? [];
}

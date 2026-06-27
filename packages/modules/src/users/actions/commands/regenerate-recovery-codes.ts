import { api } from "@workspace/common";
import { RecoveryCodesDto } from "../../models/dtos/two-factor.dto";
import { DisableTwoFactorInput } from "../../validators/two-factor.schema";

export async function regenerateRecoveryCodes(input: DisableTwoFactorInput): Promise<string[]> {
    const response = await api.post<RecoveryCodesDto>("/user/two-factor/recovery-codes", {
        password: input.password,
    });

    if (response.status !== 200) {
        throw new Error("Failed to regenerate recovery codes");
    }

    return response.data?.recovery_codes ?? [];
}

import { api } from "@workspace/common";
import { RecoveryCodesDto } from "../../models/dtos/two-factor.dto";
import { ConfirmTwoFactorInput } from "../../validators/two-factor.schema";

export async function confirmTwoFactor(input: ConfirmTwoFactorInput): Promise<string[]> {
    const response = await api.post<RecoveryCodesDto>("/user/two-factor/confirm", {
        code: input.code,
    });

    if (response.status !== 200) {
        throw new Error("Failed to confirm two-factor authentication");
    }

    return response.data?.recovery_codes ?? [];
}

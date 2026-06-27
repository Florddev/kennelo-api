import { api } from "@workspace/common";
import { AuthModel } from "../../models/auth.model";
import { AuthResponseDto } from "../../models/dtos/auth.dto";
import { TwoFactorChallengeInput } from "../../validators/two-factor.schema";
import { authService } from "../../services/auth.service";

export async function verifyTwoFactorChallenge(
    challengeToken: string,
    input: TwoFactorChallengeInput,
): Promise<AuthModel> {
    const response = await api.post<AuthResponseDto>("/login/two-factor-challenge", {
        challenge_token: challengeToken,
        code: input.code,
        recovery_code: input.recoveryCode,
    });

    if (response.status !== 200) {
        throw new Error("Failed to verify two-factor code");
    }

    if (!response.data) {
        throw new Error("No data returned from two-factor challenge");
    }

    const authModel = AuthModel.from(response.data);

    await authService.setTokens(authModel.accessToken, authModel.refreshToken);

    return authModel;
}

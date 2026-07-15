import { api } from "@workspace/common";
import { AuthModel } from "../../models/auth.model";
import { AuthResponseDto } from "../../models/dtos/auth.dto";
import { authService } from "../../services/auth.service";
import { PasswordExpiredRequired, TwoFactorRequired } from "./login-user";

export type VerifyMagicLinkInput = {
    id: string;
    expires: string;
    signature: string;
};

export async function verifyMagicLink(
    input: VerifyMagicLinkInput,
): Promise<AuthModel | TwoFactorRequired | PasswordExpiredRequired> {
    const response = await api.get<AuthResponseDto>(`/magic-link/verify/${input.id}`, {
        expires: input.expires,
        signature: input.signature,
    });

    if (response.status !== 200) {
        throw new Error("Failed to verify magic link");
    }

    if (!response.data) {
        throw new Error("No data returned from magic link verification");
    }

    if (response.data.two_factor && response.data.challenge_token) {
        return { twoFactor: true, challengeToken: response.data.challenge_token };
    }

    if (response.data.password_expired && response.data.challenge_token) {
        return { passwordExpired: true, challengeToken: response.data.challenge_token };
    }

    const authModel = AuthModel.from(response.data);

    await authService.setTokens(authModel.accessToken, authModel.refreshToken);

    return authModel;
}

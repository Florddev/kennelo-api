import { api } from "@workspace/common";
import { AuthModel } from "../../models/auth.model";
import { AuthResponseDto } from "../../models/dtos/auth.dto";
import { authService } from "../../services/auth.service";
import { TwoFactorRequired } from "./login-user";

export async function loginWithGoogle(
    token: string,
    locale?: string,
): Promise<AuthModel | TwoFactorRequired> {
    const rememberToken = await authService.getRememberToken();

    const response = await api.post<AuthResponseDto>("/login/google", {
        token,
        locale,
        remember_token: rememberToken ?? undefined,
    });

    if (response.status !== 200) {
        throw new Error("Failed to login with Google");
    }

    if (!response.data) {
        throw new Error("No data returned from Google login");
    }

    if (response.data.two_factor && response.data.challenge_token) {
        return { twoFactor: true, challengeToken: response.data.challenge_token };
    }

    const authModel = AuthModel.from(response.data);

    await authService.setTokens(authModel.accessToken, authModel.refreshToken);

    return authModel;
}

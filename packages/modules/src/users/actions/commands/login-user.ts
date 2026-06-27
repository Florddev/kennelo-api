import { api } from "@workspace/common";
import { AuthModel } from "../../models/auth.model";
import { AuthResponseDto } from "../../models/dtos/auth.dto";
import { LoginUserInput } from "../../validators/login-user.schema";
import { authService } from "../../services/auth.service";

export type TwoFactorRequired = {
    twoFactor: true;
    challengeToken: string;
};

export async function loginUser(input: LoginUserInput): Promise<AuthModel | TwoFactorRequired> {
    const rememberToken = await authService.getRememberToken();

    const response = await api.post<AuthResponseDto>("/login", {
        email: input.email,
        password: input.password,
        remember_token: rememberToken ?? undefined,
    });

    if (response.status !== 200) {
        throw new Error("Failed to login");
    }

    if (!response.data) {
        throw new Error("No data returned from login");
    }

    if (response.data.two_factor && response.data.challenge_token) {
        return { twoFactor: true, challengeToken: response.data.challenge_token };
    }

    const authModel = AuthModel.from(response.data);

    await authService.setTokens(authModel.accessToken, authModel.refreshToken);

    return authModel;
}

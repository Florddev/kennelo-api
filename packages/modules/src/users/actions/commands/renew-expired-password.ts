import { api } from "@workspace/common";
import { AuthModel } from "../../models/auth.model";
import { AuthResponseDto } from "../../models/dtos/auth.dto";
import { RenewPasswordInput } from "../../validators/renew-password.schema";
import { authService } from "../../services/auth.service";

export async function renewExpiredPassword(
    challengeToken: string,
    input: RenewPasswordInput,
): Promise<AuthModel> {
    const response = await api.post<AuthResponseDto>("/password/renew", {
        challenge_token: challengeToken,
        password: input.password,
        password_confirmation: input.passwordConfirmation,
    });

    if (response.status !== 200) {
        throw new Error("Failed to renew password");
    }

    if (!response.data) {
        throw new Error("No data returned from password renewal");
    }

    const authModel = AuthModel.from(response.data);

    await authService.setTokens(authModel.accessToken, authModel.refreshToken);

    return authModel;
}

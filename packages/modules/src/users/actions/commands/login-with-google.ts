import { api } from "@workspace/common";
import { AuthModel } from "../../models/auth.model";
import { AuthResponseDto } from "../../models/dtos/auth.dto";
import { authService } from "../../services/auth.service";

export async function loginWithGoogle(token: string, locale?: string): Promise<AuthModel> {
    const response = await api.post<AuthResponseDto>("/login/google", {
        token,
        locale,
    });

    if (response.status !== 200) {
        throw new Error("Failed to login with Google");
    }

    if (!response.data) {
        throw new Error("No data returned from Google login");
    }

    const authModel = AuthModel.from(response.data);

    await authService.setTokens(authModel.accessToken, authModel.refreshToken);

    return authModel;
}

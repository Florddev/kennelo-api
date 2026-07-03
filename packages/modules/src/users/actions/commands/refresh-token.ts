import { api } from "@workspace/common";
import { RefreshTokenResponseDto } from "../../models/dtos/auth.dto";
import { authService } from "../../services/auth.service";

export async function refreshToken(): Promise<string> {
    if (typeof window === "undefined") throw new Error("Cannot refresh token on server side");

    const refreshToken = await authService.getRefreshToken();

    if (!refreshToken) {
        throw new Error("No refresh token found");
    }

    const response = await api.post<RefreshTokenResponseDto>("/refresh", {
        refresh_token: refreshToken,
    });

    if (response.status !== 200) {
        throw new Error("Failed to refresh token");
    }

    if (!response.data) {
        throw new Error("No data returned from token refresh");
    }

    const newAccessToken = response.data.access_token;
    const newRefreshToken = response.data.refresh_token ?? refreshToken;

    await authService.setTokens(newAccessToken, newRefreshToken);

    return newAccessToken;
}

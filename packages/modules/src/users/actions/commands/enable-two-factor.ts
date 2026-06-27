import { api } from "@workspace/common";
import { TwoFactorEnableDto } from "../../models/dtos/two-factor.dto";

export async function enableTwoFactor(password: string): Promise<TwoFactorEnableDto> {
    const response = await api.post<TwoFactorEnableDto>("/user/two-factor", { password });

    if (response.status !== 200) {
        throw new Error("Failed to enable two-factor authentication");
    }

    if (!response.data) {
        throw new Error("No data returned from two-factor enable");
    }

    return response.data;
}

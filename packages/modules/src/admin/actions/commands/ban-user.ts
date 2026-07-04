import { api } from "@workspace/common";
import type { AdminUserDto } from "../../models/dtos/admin-user.dto";
import { AdminUserModel } from "../../models/admin-user.model";
import type { BanUserInput } from "../../validators/ban-user.schema";

export async function banUser(userId: string, input: BanUserInput): Promise<AdminUserModel | null> {
    const response = await api.post<AdminUserDto>(`/admin/users/${userId}/ban`, {
        reason: input.reason,
        banned_until: input.bannedUntil ?? undefined,
    });

    if (!response.data) {
        return null;
    }

    return AdminUserModel.from(response.data);
}

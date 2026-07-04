import { api } from "@workspace/common";
import type { AdminUserDto } from "../../models/dtos/admin-user.dto";
import { AdminUserModel } from "../../models/admin-user.model";
import { USER_STATUS, type UserStatusValue } from "../../types/user-status.type";

export async function updateUserStatus(
    userId: string,
    status: UserStatusValue,
): Promise<AdminUserModel | null> {
    const response = await api.put<AdminUserDto>(`/admin/users/${userId}/status`, { status });

    if (!response.data) {
        return null;
    }

    return AdminUserModel.from(response.data);
}

export function toggleActiveStatus(current: UserStatusValue | null): UserStatusValue {
    return current === USER_STATUS.ACTIVE ? USER_STATUS.INACTIVE : USER_STATUS.ACTIVE;
}

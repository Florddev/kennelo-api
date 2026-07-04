import { api } from "@workspace/common";
import type { AdminUserDto } from "../../models/dtos/admin-user.dto";
import { AdminUserModel } from "../../models/admin-user.model";

export async function unbanUser(userId: string): Promise<AdminUserModel | null> {
    const response = await api.delete<AdminUserDto>(`/admin/users/${userId}/ban`);

    if (!response.data) {
        return null;
    }

    return AdminUserModel.from(response.data);
}

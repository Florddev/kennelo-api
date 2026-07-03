import { api } from "@workspace/common";
import type { AdminUserDto } from "../../models/dtos/admin-user.dto";
import { AdminUserModel } from "../../models/admin-user.model";

export type AdminUserSortBy = "first_name" | "last_name" | "email" | "created_at";
export type AdminUserSortDir = "asc" | "desc";

export async function getAdminUsers(input?: {
    search?: string;
    role?: string;
    sortBy?: AdminUserSortBy;
    sortDir?: AdminUserSortDir;
    perPage?: number;
}): Promise<AdminUserModel[]> {
    const params: Record<string, string | number | boolean> = {};

    if (input?.search) params.search = input.search;
    if (input?.role) params.role = input.role;
    if (input?.sortBy) params.sort_by = input.sortBy;
    if (input?.sortDir) params.sort_dir = input.sortDir;
    if (input?.perPage != null) params.per_page = input.perPage;

    const response = await api.get<AdminUserDto[]>(
        "/admin/users",
        Object.keys(params).length > 0 ? params : undefined,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(AdminUserModel.from);
}

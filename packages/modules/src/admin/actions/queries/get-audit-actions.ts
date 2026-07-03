import { api } from "@workspace/common";
import type { AdminActionType } from "../../types/admin-action-type.type";
import type { AdminActionDto } from "../../models/dtos/admin-action.dto";
import { AdminActionModel } from "../../models/admin-action.model";

export async function getAuditActions(input?: {
    action?: AdminActionType;
    adminId?: string;
    userId?: string;
    perPage?: number;
}): Promise<AdminActionModel[]> {
    const params: Record<string, string | number | boolean> = {};

    if (input?.action) params.action = input.action;
    if (input?.adminId) params.admin_id = input.adminId;
    if (input?.userId) params.user_id = input.userId;
    if (input?.perPage != null) params.per_page = input.perPage;

    const response = await api.get<AdminActionDto[]>(
        "/admin/audit-actions",
        Object.keys(params).length > 0 ? params : undefined,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(AdminActionModel.from);
}

import { api } from "@workspace/common";
import type { NotificationDto } from "../../models/dtos/notification.dto";
import { NotificationModel } from "../../models/notification.model";

export async function getNotifications(input?: {
    perPage?: number;
    page?: number;
    unreadOnly?: boolean;
}): Promise<NotificationModel[]> {
    const params: Record<string, string | number | boolean> = {};
    if (input?.perPage != null) params.per_page = input.perPage;
    if (input?.page != null) params.page = input.page;
    if (input?.unreadOnly) params.unread_only = true;

    const response = await api.get<NotificationDto[]>(
        "/notifications",
        Object.keys(params).length > 0 ? params : undefined,
    );
    if (!response.data) return [];
    return response.data.map(NotificationModel.from);
}

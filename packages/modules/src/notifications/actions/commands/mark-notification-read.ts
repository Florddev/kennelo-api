import { api } from "@workspace/common";
import type { NotificationDto } from "../../models/dtos/notification.dto";
import { NotificationModel } from "../../models/notification.model";

export async function markNotificationRead(id: string): Promise<NotificationModel | null> {
    const response = await api.put<NotificationDto>(`/notifications/${id}/read`);
    return response.data ? NotificationModel.from(response.data) : null;
}

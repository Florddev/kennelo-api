import type { NotificationType } from "../../types/notification-type.type";

export type NotificationDto = {
    id: string;
    type: NotificationType;
    data: Record<string, unknown>;
    is_read?: boolean;
    read_at: string | null;
    created_at: string;
};

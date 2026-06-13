import type { NotificationType } from "../types/notification-type.type";
import type { NotificationDto } from "./dtos/notification.dto";

export class NotificationModel {
    private constructor(
        public readonly id: string,
        public readonly type: NotificationType,
        public readonly data: Record<string, unknown>,
        public readonly isRead: boolean,
        public readonly readAt: string | null,
        public readonly createdAt: string,
    ) {}

    static from(dto: NotificationDto): NotificationModel {
        return new NotificationModel(
            dto.id,
            dto.type,
            dto.data,
            dto.is_read ?? dto.read_at !== null,
            dto.read_at,
            dto.created_at,
        );
    }

    asRead(): NotificationModel {
        if (this.isRead) return this;
        return new NotificationModel(
            this.id,
            this.type,
            this.data,
            true,
            this.readAt ?? new Date().toISOString(),
            this.createdAt,
        );
    }

    str(key: string): string | undefined {
        const value = this.data[key];
        return typeof value === "string" ? value : undefined;
    }
}

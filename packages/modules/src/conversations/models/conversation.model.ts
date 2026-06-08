import { ActivityModel } from "../../activities/models/activity.model";
import { UserModel } from "../../users/models/user.model";
import { BookingThreadModel } from "./booking-thread.model";
import type { ConversationDto } from "./dtos/conversation.dto";
import { MessageModel } from "./message.model";

export class ConversationModel {
    private constructor(
        public readonly id: string,
        public readonly userId: string,
        public readonly activityId: string,
        public readonly lastMessageAt: string | null,
        public readonly user: UserModel | null,
        public readonly activity: ActivityModel | null,
        public readonly latestMessage: MessageModel | null,
        public readonly unreadCount: number | null,
        public readonly bookingThreads: BookingThreadModel[] | null,
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: ConversationDto): ConversationModel {
        return new ConversationModel(
            dto.id,
            dto.user_id,
            dto.activity_id,
            dto.last_message_at,
            dto.user ? UserModel.from(dto.user) : null,
            dto.activity ? ActivityModel.from(dto.activity) : null,
            dto.latest_message ? MessageModel.from(dto.latest_message) : null,
            dto.unread_count ?? null,
            dto.booking_threads ? dto.booking_threads.map(BookingThreadModel.from) : null,
            dto.created_at,
            dto.updated_at,
        );
    }

    hasUnreadMessages(): boolean {
        return (this.unreadCount ?? 0) > 0;
    }

    withLatestMessage(message: MessageModel, resetUnread = false): ConversationModel {
        return new ConversationModel(
            this.id,
            this.userId,
            this.activityId,
            message.createdAt,
            this.user,
            this.activity,
            message,
            resetUnread ? 0 : this.unreadCount,
            this.bookingThreads,
            this.createdAt,
            this.updatedAt,
        );
    }
}

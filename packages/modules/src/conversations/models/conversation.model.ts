import { EstablishmentModel } from "../../establishments/models/establishment.model";
import { UserModel } from "../../users/models/user.model";
import { BookingThreadModel } from "./booking-thread.model";
import type { ConversationDto } from "./dtos/conversation.dto";
import { MessageModel } from "./message.model";

export class ConversationModel {
    private constructor(
        public readonly id: string,
        public readonly userId: string,
        public readonly establishmentId: string,
        public readonly lastMessageAt: string | null,
        public readonly user: UserModel | null,
        public readonly establishment: EstablishmentModel | null,
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
            dto.establishment_id,
            dto.last_message_at,
            dto.user ? UserModel.from(dto.user) : null,
            dto.establishment ? EstablishmentModel.from(dto.establishment) : null,
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
}

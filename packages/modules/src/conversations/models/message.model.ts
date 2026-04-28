import { formatTimeOnly } from "@workspace/common";
import { BookingModel } from "../../bookings/models/booking.model";
import { UserModel } from "../../users/models/user.model";
import { MessageType } from "../types/message-type.type";
import { SenderType } from "../types/sender-type.type";
import type { MessageDto } from "./dtos/message.dto";
import { MessageFileModel } from "./message-file.model";

export class MessageModel {
    private constructor(
        public readonly id: string,
        public readonly conversationId: string,
        public readonly bookingId: string | null,
        public readonly senderId: string | null,
        public readonly senderType: SenderType,
        public readonly messageType: MessageType,
        public readonly content: string | null,
        public readonly sender: UserModel | null,
        public readonly files: MessageFileModel[] | null,
        public readonly booking: BookingModel | null,
        public readonly createdAt: string,
    ) {}

    static from(dto: MessageDto): MessageModel {
        return new MessageModel(
            dto.id,
            dto.conversation_id,
            dto.booking_id,
            dto.sender_id,
            dto.sender_type,
            dto.message_type,
            dto.content,
            dto.sender ? UserModel.from(dto.sender) : null,
            dto.files ? dto.files.map(MessageFileModel.from) : null,
            dto.booking ? BookingModel.from(dto.booking) : null,
            dto.created_at,
        );
    }

    isSystemMessage(): boolean {
        return this.senderType === "system" || this.messageType === "system";
    }

    formattedTime(locales?: Intl.LocalesArgument): string {
        return formatTimeOnly(this.createdAt, locales);
    }

    getSenderInfo() {
        return {
            avatarUrl: this.sender?.avatarUrl ?? null,
            initials: this.sender?.getInitials() ?? "?",
        };
    }

    isGroupedWith(message: MessageModel): boolean {
        return (
            !this.isSystemMessage() &&
            !message.isSystemMessage() &&
            this.messageType !== "booking_reference" &&
            message.messageType !== "booking_reference" &&
            this.senderId === message.senderId &&
            new Date(message.createdAt).getTime() - new Date(this.createdAt).getTime() <
                2 * 60 * 1000
        );
    }
}

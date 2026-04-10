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
            dto.created_at,
        );
    }

    isSystemMessage(): boolean {
        return this.senderType === "system" || this.messageType === "system";
    }
}

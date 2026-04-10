import { SenderType } from "../../types/sender-type.type";
import type { UserDto } from "../../../users/models/dtos/user.dto";
import type { MessageFileDto } from "./message-file.dto";
import { MessageType } from "../../types/message-type.type";

export type MessageDto = {
    id: string;
    conversation_id: string;
    booking_id: string | null;
    sender_id: string | null;
    sender_type: SenderType;
    message_type: MessageType;
    content: string | null;
    sender?: UserDto | null;
    files?: MessageFileDto[];
    created_at: string;
};

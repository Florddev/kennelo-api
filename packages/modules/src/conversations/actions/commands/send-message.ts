import { api } from "@workspace/common";
import type { MessageDto } from "../../models/dtos/message.dto";
import { MessageModel } from "../../models/message.model";
import type { SendMessageInput } from "../../validators/send-message.schema";

export async function sendMessage(
    conversationId: string,
    input: SendMessageInput,
): Promise<MessageModel> {
    const response = await api.post<MessageDto>(`/conversations/${conversationId}/messages`, {
        content: input.content,
        message_type: input.messageType,
        booking_id: input.bookingId,
    });

    if (!response.data) {
        throw new Error("Failed to send message");
    }

    return MessageModel.from(response.data);
}

import { api } from "@workspace/common";
import type { MessageDto } from "../../models/dtos/message.dto";
import { MessageModel } from "../../models/message.model";
import type { SendMessageInput } from "../../validators/send-message.schema";

export async function sendMessage(
    conversationId: string,
    input: SendMessageInput,
    files?: File[],
): Promise<MessageModel> {
    let body: Record<string, unknown> | FormData;

    if (files?.length) {
        const formData = new FormData();
        if (input.content) formData.append("content", input.content);
        if (input.messageType) formData.append("message_type", input.messageType);
        if (input.bookingId) formData.append("booking_id", input.bookingId);
        files.forEach((file) => formData.append("files[]", file));
        body = formData;
    } else {
        body = {
            content: input.content,
            message_type: input.messageType,
            booking_id: input.bookingId,
        };
    }

    const response = await api.post<MessageDto>(`/conversations/${conversationId}/messages`, body);

    if (!response.data) {
        throw new Error("Failed to send message");
    }

    return MessageModel.from(response.data);
}

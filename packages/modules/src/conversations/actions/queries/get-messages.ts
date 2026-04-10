import { api } from "@workspace/common";
import type { MessageDto } from "../../models/dtos/message.dto";
import { MessageModel } from "../../models/message.model";

export async function getMessages(
    conversationId: string,
    input?: { bookingId?: string | null; perPage?: number },
): Promise<MessageModel[]> {
    const params: Record<string, string | number | boolean> = {};

    if (input?.bookingId) params.booking_id = input.bookingId;
    if (input?.perPage != null) params.per_page = input.perPage;

    const response = await api.get<MessageDto[]>(
        `/conversations/${conversationId}/messages`,
        Object.keys(params).length > 0 ? params : undefined,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(MessageModel.from);
}

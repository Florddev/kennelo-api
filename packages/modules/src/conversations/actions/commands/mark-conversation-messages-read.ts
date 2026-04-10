import { api } from "@workspace/common";
import type { MarkedReadDto } from "../../models/dtos/marked-read.dto";

export async function markConversationMessagesRead(conversationId: string): Promise<number> {
    const response = await api.put<MarkedReadDto>(`/conversations/${conversationId}/messages/read`);

    if (!response.data) {
        return 0;
    }

    return response.data.marked_count;
}

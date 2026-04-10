import { api } from "@workspace/common";
import type { UnreadCountDto } from "../../models/dtos/unread-count.dto";

export async function getUnreadConversationsCount(): Promise<number> {
    const response = await api.get<UnreadCountDto>("/conversations/unread-count");

    if (!response.data) {
        return 0;
    }

    return response.data.unread_count;
}

import { api } from "@workspace/common";
import type { UnreadCountDto } from "../../models/dtos/unread-count.dto";

export async function getUnreadNotificationsCount(): Promise<number> {
    const response = await api.get<UnreadCountDto>("/notifications/unread-count");
    return response.data?.unread_count ?? 0;
}

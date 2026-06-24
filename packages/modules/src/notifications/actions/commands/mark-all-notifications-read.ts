import { api } from "@workspace/common";
import type { MarkedCountDto } from "../../models/dtos/marked-count.dto";

export async function markAllNotificationsRead(): Promise<number> {
    const response = await api.put<MarkedCountDto>("/notifications/read-all");
    return response.data?.marked_count ?? 0;
}

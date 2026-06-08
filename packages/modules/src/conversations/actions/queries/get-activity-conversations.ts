import { api } from "@workspace/common";
import type { ConversationDto } from "../../models/dtos/conversation.dto";
import { ConversationModel } from "../../models/conversation.model";

export async function getActivityConversations(
    activityId: string,
    input?: { perPage?: number },
): Promise<ConversationModel[]> {
    const params: Record<string, string | number | boolean> = {};

    if (input?.perPage != null) params.per_page = input.perPage;

    const response = await api.get<ConversationDto[]>(
        `/activities/${activityId}/conversations`,
        Object.keys(params).length > 0 ? params : undefined,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(ConversationModel.from);
}

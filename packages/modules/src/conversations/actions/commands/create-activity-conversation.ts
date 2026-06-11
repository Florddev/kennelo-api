import { api } from "@workspace/common";
import type { ConversationDto } from "../../models/dtos/conversation.dto";
import { ConversationModel } from "../../models/conversation.model";

export async function createActivityConversation(
    activityId: string,
    userId?: string,
): Promise<ConversationModel> {
    const body = userId ? { user_id: userId } : undefined;
    const response = await api.post<ConversationDto>(
        `/activities/${activityId}/conversations`,
        body,
    );

    if (!response.data) {
        throw new Error("Failed to create conversation");
    }

    return ConversationModel.from(response.data);
}

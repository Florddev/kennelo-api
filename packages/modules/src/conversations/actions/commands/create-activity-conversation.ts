import { api } from "@workspace/common";
import type { ConversationDto } from "../../models/dtos/conversation.dto";
import { ConversationModel } from "../../models/conversation.model";

export async function createActivityConversation(activityId: string): Promise<ConversationModel> {
    const response = await api.post<ConversationDto>(`/activities/${activityId}/conversation`);

    if (!response.data) {
        throw new Error("Failed to create conversation");
    }

    return ConversationModel.from(response.data);
}

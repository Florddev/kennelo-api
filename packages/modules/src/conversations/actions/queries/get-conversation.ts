import { api } from "@workspace/common";
import type { ConversationDto } from "../../models/dtos/conversation.dto";
import { ConversationModel } from "../../models/conversation.model";

export async function getConversation(conversationId: string): Promise<ConversationModel> {
    const response = await api.get<ConversationDto>(`/conversations/${conversationId}`);

    if (!response.data) {
        throw new Error("No data returned");
    }

    return ConversationModel.from(response.data);
}

import { api } from "@workspace/common";
import type { ConversationDto } from "../../models/dtos/conversation.dto";
import { ConversationModel } from "../../models/conversation.model";

export async function createPetConversation(
    petId: string,
    activityId?: string,
): Promise<ConversationModel> {
    const body = activityId ? { activity_id: activityId } : undefined;
    const response = await api.post<ConversationDto>(`/pets/${petId}/conversation`, body);

    if (!response.data) {
        throw new Error("Failed to create conversation");
    }

    return ConversationModel.from(response.data);
}

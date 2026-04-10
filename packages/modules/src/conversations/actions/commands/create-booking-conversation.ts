import { api } from "@workspace/common";
import type { ConversationDto } from "../../models/dtos/conversation.dto";
import { ConversationModel } from "../../models/conversation.model";

export async function createBookingConversation(bookingId: string): Promise<ConversationModel> {
    const response = await api.post<ConversationDto>(`/bookings/${bookingId}/conversation`);

    if (!response.data) {
        throw new Error("Failed to create conversation");
    }

    return ConversationModel.from(response.data);
}

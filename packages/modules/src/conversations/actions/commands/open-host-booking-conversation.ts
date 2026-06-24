import { api } from "@workspace/common";
import type { ConversationDto } from "../../models/dtos/conversation.dto";
import { ConversationModel } from "../../models/conversation.model";

export async function openHostBookingConversation(bookingId: string): Promise<ConversationModel> {
    const response = await api.post<ConversationDto>(`/hosting/bookings/${bookingId}/conversation`);

    if (!response.data) {
        throw new Error("Failed to open conversation");
    }

    return ConversationModel.from(response.data);
}

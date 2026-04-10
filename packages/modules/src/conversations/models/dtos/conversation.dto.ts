import type { EstablishmentDto } from "../../../establishments/models/dtos/establishment.dto";
import type { UserDto } from "../../../users/models/dtos/user.dto";
import type { BookingThreadDto } from "./booking-thread.dto";
import type { MessageDto } from "./message.dto";

export type ConversationDto = {
    id: string;
    user_id: string;
    establishment_id: string;
    last_message_at: string | null;
    user?: UserDto | null;
    establishment?: EstablishmentDto | null;
    latest_message?: MessageDto | null;
    unread_count?: number;
    booking_threads?: BookingThreadDto[];
    created_at: string;
    updated_at: string;
};

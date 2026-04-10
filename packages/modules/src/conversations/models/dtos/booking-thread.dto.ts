import type { BookingDto } from "../../../bookings/models/dtos/booking.dto";

export type BookingThreadDto = {
    booking_id: string;
    conversation_id: string;
    is_active: boolean;
    archived_at: string | null;
    booking?: BookingDto | null;
};

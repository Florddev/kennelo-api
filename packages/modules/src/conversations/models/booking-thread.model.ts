import { BookingModel } from "../../bookings/models/booking.model";
import type { BookingThreadDto } from "./dtos/booking-thread.dto";

export class BookingThreadModel {
    private constructor(
        public readonly bookingId: string,
        public readonly conversationId: string,
        public readonly isActive: boolean,
        public readonly archivedAt: string | null,
        public readonly booking: BookingModel | null,
    ) {}

    static from(dto: BookingThreadDto): BookingThreadModel {
        return new BookingThreadModel(
            dto.booking_id,
            dto.conversation_id,
            dto.is_active,
            dto.archived_at,
            dto.booking ? BookingModel.from(dto.booking) : null,
        );
    }
}

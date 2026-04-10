import { api } from "@workspace/common";
import type { BookingDto } from "../../models/dtos/booking.dto";
import { BookingModel } from "../../models/booking.model";

export async function cancelEstablishmentBooking(
    establishmentId: string,
    bookingId: string,
): Promise<BookingModel> {
    const response = await api.put<BookingDto>(
        `/establishments/${establishmentId}/bookings/${bookingId}/cancel`,
    );

    if (!response.data) {
        throw new Error("Failed to cancel booking");
    }

    return BookingModel.from(response.data);
}

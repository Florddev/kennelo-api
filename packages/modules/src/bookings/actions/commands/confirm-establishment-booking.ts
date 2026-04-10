import { api } from "@workspace/common";
import type { BookingDto } from "../../models/dtos/booking.dto";
import { BookingModel } from "../../models/booking.model";

export async function confirmEstablishmentBooking(
    establishmentId: string,
    bookingId: string,
): Promise<BookingModel> {
    const response = await api.put<BookingDto>(
        `/establishments/${establishmentId}/bookings/${bookingId}/confirm`,
    );

    if (!response.data) {
        throw new Error("Failed to confirm booking");
    }

    return BookingModel.from(response.data);
}

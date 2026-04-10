import { api } from "@workspace/common";
import type { BookingDto } from "../../models/dtos/booking.dto";
import { BookingModel } from "../../models/booking.model";

export async function completeEstablishmentBooking(
    establishmentId: string,
    bookingId: string,
): Promise<BookingModel> {
    const response = await api.put<BookingDto>(
        `/establishments/${establishmentId}/bookings/${bookingId}/complete`,
    );

    if (!response.data) {
        throw new Error("Failed to complete booking");
    }

    return BookingModel.from(response.data);
}

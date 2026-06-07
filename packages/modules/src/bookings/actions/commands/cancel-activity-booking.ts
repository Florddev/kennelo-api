import { api } from "@workspace/common";
import type { BookingDto } from "../../models/dtos/booking.dto";
import { BookingModel } from "../../models/booking.model";

export async function cancelActivityBooking(
    activityId: string,
    bookingId: string,
): Promise<BookingModel> {
    const response = await api.put<BookingDto>(
        `/activities/${activityId}/bookings/${bookingId}/cancel`,
    );

    if (!response.data) {
        throw new Error("Failed to cancel booking");
    }

    return BookingModel.from(response.data);
}

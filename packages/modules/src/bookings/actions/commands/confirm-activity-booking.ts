import { api } from "@workspace/common";
import type { BookingDto } from "../../models/dtos/booking.dto";
import { BookingModel } from "../../models/booking.model";

export async function confirmActivityBooking(
    activityId: string,
    bookingId: string,
    message?: string,
): Promise<BookingModel> {
    const response = await api.put<BookingDto>(
        `/activities/${activityId}/bookings/${bookingId}/confirm`,
        message ? { message } : undefined,
    );

    if (!response.data) {
        throw new Error("Failed to confirm booking");
    }

    return BookingModel.from(response.data);
}

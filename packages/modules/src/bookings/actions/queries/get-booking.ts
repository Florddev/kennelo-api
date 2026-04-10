import { api } from "@workspace/common";
import type { BookingDto } from "../../models/dtos/booking.dto";
import { BookingModel } from "../../models/booking.model";

export async function getBooking(bookingId: string): Promise<BookingModel> {
    const response = await api.get<BookingDto>(`/bookings/${bookingId}`);

    if (!response.data) {
        throw new Error("No data returned");
    }

    return BookingModel.from(response.data);
}

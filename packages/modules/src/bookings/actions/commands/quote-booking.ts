import { api } from "@workspace/common";
import type { BookingQuoteDto } from "../../models/dtos/booking-quote.dto";
import { BookingQuoteModel } from "../../models/booking-quote.model";

export type BookingQuoteInput = {
    activityId: string;
    checkInDate: string;
    checkOutDate: string;
    petIds: string[];
    serviceIds?: string[];
};

export async function quoteBooking(input: BookingQuoteInput): Promise<BookingQuoteModel> {
    const response = await api.post<BookingQuoteDto>("/bookings/quote", {
        activity_id: input.activityId,
        check_in_date: input.checkInDate,
        check_out_date: input.checkOutDate,
        pet_ids: input.petIds,
        service_ids: input.serviceIds,
    });

    if (!response.data) {
        throw new Error("Failed to fetch booking quote");
    }

    return BookingQuoteModel.from(response.data);
}

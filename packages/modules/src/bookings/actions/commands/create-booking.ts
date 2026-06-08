import { api } from "@workspace/common";
import type { BookingDto } from "../../models/dtos/booking.dto";
import { BookingModel } from "../../models/booking.model";
import type { CreateBookingInput } from "../../validators/create-booking.schema";

export async function createBooking(input: CreateBookingInput): Promise<BookingModel> {
    const response = await api.post<BookingDto>("/bookings", {
        activity_id: input.activityId,
        check_in_date: input.checkInDate,
        check_out_date: input.checkOutDate,
        pet_ids: input.petIds,
        service_ids: input.serviceIds,
        special_requests: input.specialRequests,
        payment_method_id: input.paymentMethodId,
        save_payment_method: input.savePaymentMethod ?? false,
    });

    if (!response.data) {
        throw new Error("Failed to create booking");
    }

    return BookingModel.from(response.data);
}

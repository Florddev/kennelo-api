import { api } from "@workspace/common";
import type { BookingDto } from "../../models/dtos/booking.dto";
import { BookingModel } from "../../models/booking.model";
import type { BookingStatus } from "../../types/booking-status.type";

export async function getBookings(input?: {
    status?: BookingStatus;
    perPage?: number;
}): Promise<BookingModel[]> {
    const params: Record<string, string | number | boolean> = {};

    if (input?.status) params.status = input.status;
    if (input?.perPage != null) params.per_page = input.perPage;

    const response = await api.get<BookingDto[]>(
        "/bookings",
        Object.keys(params).length > 0 ? params : undefined,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(BookingModel.from);
}

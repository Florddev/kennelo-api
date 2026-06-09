"use client";

import { useQuery } from "@tanstack/react-query";
import { getBooking } from "@workspace/modules/bookings";

export function useBooking(bookingId: string) {
    const query = useQuery({
        queryKey: ["booking", bookingId],
        queryFn: () => getBooking(bookingId),
        enabled: Boolean(bookingId),
    });

    return {
        booking: query.data ?? null,
        isLoading: query.isLoading,
        error: query.error,
    };
}

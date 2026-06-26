"use client";

import { useQuery } from "@tanstack/react-query";
import { quoteBooking } from "@workspace/modules/bookings";

type UseBookingQuoteParams = {
    activityId: string;
    checkInDate: string | null;
    checkOutDate: string | null;
    petIds: string[];
};

export function useBookingQuote({
    activityId,
    checkInDate,
    checkOutDate,
    petIds,
}: UseBookingQuoteParams) {
    const enabled = Boolean(activityId && checkInDate && checkOutDate && petIds.length > 0);

    const { data, isFetching } = useQuery({
        queryKey: ["booking", "quote", activityId, checkInDate, checkOutDate, [...petIds].sort()],
        queryFn: () =>
            quoteBooking({
                activityId,
                checkInDate: checkInDate as string,
                checkOutDate: checkOutDate as string,
                petIds,
            }),
        enabled,
    });

    return {
        quote: data ?? null,
        isLoading: enabled && isFetching,
    };
}

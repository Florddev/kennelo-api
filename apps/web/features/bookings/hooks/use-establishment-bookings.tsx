"use client";

import { useQuery } from "@tanstack/react-query";
import { getEstablishmentBookings, type BookingStatus } from "@workspace/modules/bookings";

type Options = {
    status?: BookingStatus;
};

export function useEstablishmentBookings(establishmentId: string, options: Options = {}) {
    const query = useQuery({
        queryKey: ["establishment-bookings", establishmentId, options.status ?? "all"],
        queryFn: () =>
            getEstablishmentBookings(
                establishmentId,
                options.status ? { status: options.status } : undefined,
            ),
        enabled: Boolean(establishmentId),
    });

    return {
        bookings: query.data ?? [],
        isLoading: query.isLoading,
        error: query.error,
        refetch: query.refetch,
    };
}

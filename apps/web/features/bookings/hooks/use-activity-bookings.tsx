"use client";

import { useQuery } from "@tanstack/react-query";
import { getActivityBookings, type BookingStatus } from "@workspace/modules/bookings";

type Options = {
    status?: BookingStatus;
};

export function useActivityBookings(activityId: string, options: Options = {}) {
    const query = useQuery({
        queryKey: ["activity-bookings", activityId, options.status ?? "all"],
        queryFn: () =>
            getActivityBookings(
                activityId,
                options.status ? { status: options.status } : undefined,
            ),
        enabled: Boolean(activityId),
    });

    return {
        bookings: query.data ?? [],
        isLoading: query.isLoading,
        error: query.error,
        refetch: query.refetch,
    };
}

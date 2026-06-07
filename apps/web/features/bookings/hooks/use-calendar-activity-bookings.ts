"use client";

import { useMemo } from "react";
import { useQueries } from "@tanstack/react-query";

import { getActivityBookings, type BookingModel } from "@workspace/modules/bookings";

import { getMonthRange } from "../lib/calendar-grid";

type UseCalendarActivityBookingsParams = {
    activityIds: string[];
    focusedMonth: Date;
    weekStartsOn: 0 | 1;
};

type UseCalendarActivityBookingsResult = {
    bookingsByActivityId: Record<string, BookingModel[]>;
    isLoading: boolean;
    isError: boolean;
};

export function useCalendarActivityBookings({
    activityIds,
    focusedMonth,
    weekStartsOn,
}: UseCalendarActivityBookingsParams): UseCalendarActivityBookingsResult {
    const { gridStart, gridEnd } = getMonthRange(focusedMonth, weekStartsOn);
    const dateFrom = gridStart.toISOString().slice(0, 10);
    const dateTo = gridEnd.toISOString().slice(0, 10);

    const queries = useQueries({
        queries: activityIds.map((activityId) => ({
            queryKey: ["hosting-calendar-bookings", activityId, dateFrom, dateTo],
            queryFn: () =>
                getActivityBookings(activityId, {
                    dateFrom,
                    dateTo,
                    perPage: 100,
                }),
            staleTime: 60_000,
        })),
    });

    const bookingsByActivityId = useMemo(() => {
        const result: Record<string, BookingModel[]> = {};
        activityIds.forEach((activityId, idx) => {
            result[activityId] = queries[idx]?.data ?? [];
        });
        return result;
    }, [activityIds, queries]);

    return {
        bookingsByActivityId,
        isLoading: queries.some((q) => q.isLoading),
        isError: queries.some((q) => q.isError),
    };
}

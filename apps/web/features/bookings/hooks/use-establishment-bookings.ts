"use client";

import { useMemo } from "react";
import { useQueries } from "@tanstack/react-query";

import { getEstablishmentBookings, type BookingModel } from "@workspace/modules/bookings";

import { getMonthRange } from "../lib/calendar-grid";

type UseEstablishmentBookingsParams = {
    establishmentIds: string[];
    focusedMonth: Date;
    weekStartsOn: 0 | 1;
};

type UseEstablishmentBookingsResult = {
    bookingsByEstablishmentId: Record<string, BookingModel[]>;
    isLoading: boolean;
    isError: boolean;
};

export function useEstablishmentBookings({
    establishmentIds,
    focusedMonth,
    weekStartsOn,
}: UseEstablishmentBookingsParams): UseEstablishmentBookingsResult {
    const { gridStart, gridEnd } = getMonthRange(focusedMonth, weekStartsOn);
    const dateFrom = gridStart.toISOString().slice(0, 10);
    const dateTo = gridEnd.toISOString().slice(0, 10);

    const queries = useQueries({
        queries: establishmentIds.map((establishmentId) => ({
            queryKey: ["hosting-calendar-bookings", establishmentId, dateFrom, dateTo],
            queryFn: () =>
                getEstablishmentBookings(establishmentId, {
                    dateFrom,
                    dateTo,
                    perPage: 100,
                }),
            staleTime: 60_000,
        })),
    });

    const bookingsByEstablishmentId = useMemo(() => {
        const result: Record<string, BookingModel[]> = {};
        establishmentIds.forEach((establishmentId, idx) => {
            result[establishmentId] = queries[idx]?.data ?? [];
        });
        return result;
    }, [establishmentIds, queries]);

    return {
        bookingsByEstablishmentId,
        isLoading: queries.some((q) => q.isLoading),
        isError: queries.some((q) => q.isError),
    };
}

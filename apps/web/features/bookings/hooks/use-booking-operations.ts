"use client";

import { useQuery } from "@tanstack/react-query";

import { getBookingOperations, type FinancialOperationModel } from "@workspace/modules/bookings";

export const bookingOperationsQueryKey = (bookingId: string) => ["booking-operations", bookingId];

type UseBookingOperationsResult = {
    operations: FinancialOperationModel[];
    isLoading: boolean;
    isError: boolean;
};

export function useBookingOperations(
    activityId: string,
    bookingId: string,
    enabled = true,
): UseBookingOperationsResult {
    const { data, isLoading, isError } = useQuery({
        queryKey: bookingOperationsQueryKey(bookingId),
        queryFn: () => getBookingOperations(activityId, bookingId),
        staleTime: 60_000,
        enabled: enabled && Boolean(activityId) && Boolean(bookingId),
    });

    return {
        operations: data ?? [],
        isLoading,
        isError,
    };
}

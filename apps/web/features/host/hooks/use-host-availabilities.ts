"use client";

import { useQuery } from "@tanstack/react-query";
import { getAvailabilitiesRange } from "@workspace/modules/establishments";
import { toApiDate } from "@workspace/common";

function firstDayOfMonth(date: Date): Date {
    const result = new Date(date);
    result.setDate(1);
    result.setHours(0, 0, 0, 0);
    return result;
}

function lastDayOfNextMonth(date: Date): Date {
    const result = new Date(date);
    result.setMonth(result.getMonth() + 2);
    result.setDate(0);
    result.setHours(23, 59, 59, 999);
    return result;
}

export function useHostAvailabilities(establishmentId: string) {
    const today = new Date();
    const startDate = toApiDate(firstDayOfMonth(today));
    const endDate = toApiDate(lastDayOfNextMonth(today));

    const { data, isLoading } = useQuery({
        queryKey: ["host", "availabilities", establishmentId, startDate, endDate],
        queryFn: () => getAvailabilitiesRange(establishmentId, startDate, endDate),
        enabled: Boolean(establishmentId),
    });

    return {
        availabilities: data ?? [],
        isLoading,
    };
}

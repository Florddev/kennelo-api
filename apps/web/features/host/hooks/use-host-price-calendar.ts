"use client";

import { useQuery } from "@tanstack/react-query";
import { getActivityPriceCalendar } from "@workspace/modules/activities";
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

export function useHostPriceCalendar(activityId: string) {
    const today = new Date();
    const from = toApiDate(firstDayOfMonth(today));
    const to = toApiDate(lastDayOfNextMonth(today));

    const { data, isLoading } = useQuery({
        queryKey: ["host", "price-calendar", activityId, from, to],
        queryFn: () => getActivityPriceCalendar(activityId, from, to),
        enabled: Boolean(activityId),
    });

    return {
        priceMap: data ?? {},
        isLoading,
    };
}

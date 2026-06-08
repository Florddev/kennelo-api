"use client";

import { useQuery } from "@tanstack/react-query";

import { getCycles, type ActivityCycleModel } from "@workspace/modules/activities";

export const activityCyclesQueryKey = (activityId: string) => ["activity-cycles", activityId];

type UseActivityCyclesResult = {
    cycles: ActivityCycleModel[];
    isLoading: boolean;
    isError: boolean;
};

export function useActivityCycles(activityId: string): UseActivityCyclesResult {
    const { data, isLoading, isError } = useQuery({
        queryKey: activityCyclesQueryKey(activityId),
        queryFn: () => getCycles(activityId),
        staleTime: 60_000,
        enabled: Boolean(activityId),
    });

    return {
        cycles: data ?? [],
        isLoading,
        isError,
    };
}

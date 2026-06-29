"use client";

import { useQuery } from "@tanstack/react-query";
import { getActivityPublicCycles } from "@workspace/modules/activities";

export function useHostPublicCycles(activityId: string) {
    const { data, isLoading } = useQuery({
        queryKey: ["host", "public-cycles", activityId],
        queryFn: () => getActivityPublicCycles(activityId),
        enabled: Boolean(activityId),
    });

    return {
        cycles: data ?? [],
        isLoading,
    };
}

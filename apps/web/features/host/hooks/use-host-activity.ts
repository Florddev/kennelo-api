"use client";

import { useQuery } from "@tanstack/react-query";
import { getActivity, getActivityCycleSettings } from "@workspace/modules/activities";

export function useHostActivity(id: string) {
    const activityQuery = useQuery({
        queryKey: ["host", "activity", id],
        queryFn: () => getActivity(id),
        enabled: Boolean(id),
    });

    const capacitiesQuery = useQuery({
        queryKey: ["host", "activity", id, "capacities"],
        queryFn: () => getActivityCycleSettings(id),
        enabled: Boolean(id),
    });

    return {
        activity: activityQuery.data ?? null,
        capacities: capacitiesQuery.data ?? [],
        isLoading: activityQuery.isLoading || capacitiesQuery.isLoading,
        error: activityQuery.error ?? capacitiesQuery.error,
    };
}

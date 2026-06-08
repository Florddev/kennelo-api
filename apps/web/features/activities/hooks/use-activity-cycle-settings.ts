"use client";

import { useQuery } from "@tanstack/react-query";

import {
    getActivityCycleSettings,
    type ActivityCycleSettingModel,
} from "@workspace/modules/activities";

type UseActivityCycleSettingsResult = {
    settings: ActivityCycleSettingModel[];
    isLoading: boolean;
    isError: boolean;
};

export function useActivityCycleSettings(
    activityId: string,
    date?: string,
): UseActivityCycleSettingsResult {
    const { data, isLoading, isError } = useQuery({
        queryKey: ["activity-cycle-settings", activityId, date ?? null],
        queryFn: () => getActivityCycleSettings(activityId, date),
        staleTime: 60_000,
        enabled: Boolean(activityId),
    });

    return {
        settings: data ?? [],
        isLoading,
        isError,
    };
}

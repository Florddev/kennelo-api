"use client";

import { useQuery } from "@tanstack/react-query";

import { getActivity, type ActivityModel } from "@workspace/modules/activities";

type UseActivityResult = {
    activity: ActivityModel | null;
    isLoading: boolean;
    isError: boolean;
};

export function activityQueryKey(id: string): [string, string] {
    return ["activity", id];
}

export function useActivity(id: string): UseActivityResult {
    const { data, isLoading, isError } = useQuery({
        queryKey: activityQueryKey(id),
        queryFn: () => getActivity(id),
        staleTime: 60_000,
        enabled: Boolean(id),
    });

    return {
        activity: data ?? null,
        isLoading,
        isError,
    };
}

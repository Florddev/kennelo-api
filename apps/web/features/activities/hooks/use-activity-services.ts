"use client";

import { useQuery } from "@tanstack/react-query";

import { getActivityServices, type ServiceModel } from "@workspace/modules/services";

export const activityServicesQueryKey = (activityId: string) => ["activity-services", activityId];

type UseActivityServicesResult = {
    services: ServiceModel[];
    isLoading: boolean;
    isError: boolean;
};

export function useActivityServices(activityId: string): UseActivityServicesResult {
    const { data, isLoading, isError } = useQuery({
        queryKey: activityServicesQueryKey(activityId),
        queryFn: () => getActivityServices(activityId),
        staleTime: 60_000,
        enabled: Boolean(activityId),
    });

    return {
        services: data ?? [],
        isLoading,
        isError,
    };
}

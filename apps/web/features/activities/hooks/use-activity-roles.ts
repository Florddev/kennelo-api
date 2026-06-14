"use client";

import { useQuery } from "@tanstack/react-query";

import { getActivityRoles, type ActivityRoleModel } from "@workspace/modules/activities";

export const activityRolesQueryKey = (activityId: string) => ["activity-roles", activityId];

type UseActivityRolesResult = {
    roles: ActivityRoleModel[];
    isLoading: boolean;
    isError: boolean;
};

export function useActivityRoles(activityId: string): UseActivityRolesResult {
    const { data, isLoading, isError } = useQuery({
        queryKey: activityRolesQueryKey(activityId),
        queryFn: () => getActivityRoles(activityId),
        staleTime: 60_000,
        enabled: Boolean(activityId),
    });

    return {
        roles: data ?? [],
        isLoading,
        isError,
    };
}

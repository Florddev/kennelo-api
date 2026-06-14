"use client";

import { useQuery } from "@tanstack/react-query";

import {
    getActivityCollaborators,
    type ActivityCollaboratorModel,
} from "@workspace/modules/activities";

export const activityCollaboratorsQueryKey = (activityId: string) => [
    "activity-collaborators",
    activityId,
];

type UseActivityCollaboratorsResult = {
    collaborators: ActivityCollaboratorModel[];
    isLoading: boolean;
    isError: boolean;
};

export function useActivityCollaborators(activityId: string): UseActivityCollaboratorsResult {
    const { data, isLoading, isError } = useQuery({
        queryKey: activityCollaboratorsQueryKey(activityId),
        queryFn: () => getActivityCollaborators(activityId),
        staleTime: 60_000,
        enabled: Boolean(activityId),
    });

    return {
        collaborators: data ?? [],
        isLoading,
        isError,
    };
}

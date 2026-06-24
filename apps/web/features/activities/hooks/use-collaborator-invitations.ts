"use client";

import { useQuery } from "@tanstack/react-query";

import {
    getMyCollaboratorInvitations,
    type ActivityCollaboratorModel,
} from "@workspace/modules/activities";

export const collaboratorInvitationsQueryKey = () => ["collaborator-invitations"];

type UseCollaboratorInvitationsResult = {
    invitations: ActivityCollaboratorModel[];
    isLoading: boolean;
    isError: boolean;
};

export function useCollaboratorInvitations(enabled = true): UseCollaboratorInvitationsResult {
    const { data, isLoading, isError } = useQuery({
        queryKey: collaboratorInvitationsQueryKey(),
        queryFn: () => getMyCollaboratorInvitations(),
        staleTime: 60_000,
        enabled,
    });

    return {
        invitations: data ?? [],
        isLoading,
        isError,
    };
}

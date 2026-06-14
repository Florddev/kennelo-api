"use client";

import { useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { Check, X } from "lucide-react";

import {
    type ActivityCollaboratorModel,
    acceptCollaboratorInvitation,
    declineCollaboratorInvitation,
} from "@workspace/modules/activities";
import { Button } from "@workspace/ui/components/button";
import { Card } from "@workspace/ui/components/card";

import { useAsyncState } from "@/hooks/use-async-state";
import {
    collaboratorInvitationsQueryKey,
    useCollaboratorInvitations,
} from "../hooks/use-collaborator-invitations";

export function CollaboratorInvitationsList() {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { invitations, isLoading } = useCollaboratorInvitations();
    const { execute } = useAsyncState();

    if (isLoading || invitations.length === 0) {
        return null;
    }

    const invalidate = () =>
        queryClient.invalidateQueries({ queryKey: collaboratorInvitationsQueryKey() });

    const accept = (invitation: ActivityCollaboratorModel) =>
        execute(() => acceptCollaboratorInvitation(invitation.activityId), {
            displayError: true,
            onSuccess: () => {
                invalidate();
                toast.success(t("features.activities.invitations.accepted"));
            },
        });

    const decline = (invitation: ActivityCollaboratorModel) =>
        execute(() => declineCollaboratorInvitation(invitation.activityId), {
            displayError: true,
            onSuccess: () => {
                invalidate();
                toast.success(t("features.activities.invitations.declined"));
            },
        });

    return (
        <Card data-slot="collaborator-invitations" className="flex flex-col gap-3 p-4">
            <div className="flex flex-col gap-0.5">
                <h3 className="text-base font-semibold">
                    {t("features.activities.invitations.title")}
                </h3>
                <p className="text-sm text-muted-foreground">
                    {t("features.activities.invitations.description")}
                </p>
            </div>
            <div className="flex flex-col gap-2">
                {invitations.map((invitation) => (
                    <div
                        key={invitation.activityId}
                        className="flex items-center justify-between gap-3 rounded-2xl border border-border p-3"
                    >
                        <span className="min-w-0 truncate text-sm font-medium">
                            {invitation.activity?.name ?? "—"}
                        </span>
                        <div className="flex shrink-0 items-center gap-2">
                            <Button size="sm" variant="outline" onClick={() => decline(invitation)}>
                                <X className="size-4" />
                                {t("features.activities.invitations.decline")}
                            </Button>
                            <Button size="sm" onClick={() => accept(invitation)}>
                                <Check className="size-4" />
                                {t("features.activities.invitations.accept")}
                            </Button>
                        </div>
                    </div>
                ))}
            </div>
        </Card>
    );
}

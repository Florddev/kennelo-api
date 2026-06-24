"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { Plus, Trash2, UserCog, Users } from "lucide-react";

import {
    type ActivityCollaboratorModel,
    type CollaboratorStatus,
    removeCollaborator,
} from "@workspace/modules/activities";
import { Badge } from "@workspace/ui/components/badge";
import { Button } from "@workspace/ui/components/button";
import { Input } from "@workspace/ui/components/input";

import { useAuth } from "@/features/auth";
import { UserAvatar } from "@/features/auth/components/user-avatar";
import { useAsyncState } from "@/hooks/use-async-state";
import { useActivity } from "../hooks/use-activity";
import {
    activityCollaboratorsQueryKey,
    useActivityCollaborators,
} from "../hooks/use-activity-collaborators";
import { useActivityRoles } from "../hooks/use-activity-roles";
import { ActivityDataTable, type DataTableColumn } from "./activity-data-table";
import { AssignRoleDialog } from "./assign-role-dialog";
import { InviteCollaboratorDialog } from "./invite-collaborator-dialog";

const STATUS_VARIANT: Record<CollaboratorStatus, "secondary" | "default" | "destructive"> = {
    pending: "secondary",
    accepted: "default",
    refused: "destructive",
};

export function ActivityCollaboratorsManager({ activityId }: { activityId: string }) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { user } = useAuth();
    const { activity } = useActivity(activityId);
    const { collaborators, isLoading } = useActivityCollaborators(activityId);
    const { roles } = useActivityRoles(activityId);
    const { execute } = useAsyncState();

    const [search, setSearch] = useState("");
    const [inviteOpen, setInviteOpen] = useState(false);
    const [assignTarget, setAssignTarget] = useState<ActivityCollaboratorModel | null>(null);

    const canManage =
        Boolean(user) &&
        (user?.id === activity?.managerId || (user?.hasAnyRoles(["admin"]) ?? false));

    const remove = (collaborator: ActivityCollaboratorModel) =>
        execute(() => removeCollaborator(activityId, collaborator.userId), {
            displayError: true,
            onSuccess: () => {
                queryClient.invalidateQueries({
                    queryKey: activityCollaboratorsQueryKey(activityId),
                });
                toast.success(t("features.activities.manager.collaborators.removed"));
            },
        });

    const columns: DataTableColumn<ActivityCollaboratorModel>[] = [
        {
            key: "name",
            header: t("features.activities.manager.collaborators.columns.name"),
            cell: (collaborator) => (
                <div className="flex min-w-0 items-center gap-3">
                    <UserAvatar
                        user={collaborator.user ?? undefined}
                        className="size-8"
                        size="sm"
                    />
                    <span className="truncate font-medium">
                        {collaborator.user?.getFullName() ?? "—"}
                    </span>
                </div>
            ),
        },
        {
            key: "email",
            header: t("features.activities.manager.collaborators.columns.email"),
            cellClassName: "text-muted-foreground",
            cell: (collaborator) => collaborator.user?.email ?? "—",
        },
        {
            key: "status",
            header: t("features.activities.manager.collaborators.columns.status"),
            cell: (collaborator) => (
                <Badge variant={STATUS_VARIANT[collaborator.status]} size="sm">
                    {t(`features.activities.manager.collaborators.statuses.${collaborator.status}`)}
                </Badge>
            ),
        },
        {
            key: "role",
            header: t("features.activities.manager.collaborators.columns.role"),
            cell: (collaborator) =>
                collaborator.role ? (
                    <Badge variant="outline" size="sm">
                        {collaborator.role.name}
                    </Badge>
                ) : (
                    <span className="text-muted-foreground">—</span>
                ),
        },
    ];

    if (canManage) {
        columns.push({
            key: "actions",
            header: "",
            cellClassName: "text-end",
            cell: (collaborator) => (
                <div className="flex items-center justify-end gap-1">
                    {collaborator.isAccepted() && (
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => setAssignTarget(collaborator)}
                            aria-label={t("features.activities.manager.collaborators.assignRole")}
                        >
                            <UserCog className="size-4" />
                        </Button>
                    )}
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => remove(collaborator)}
                        aria-label={t("common.actions.delete")}
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
            ),
        });
    }

    return (
        <div data-slot="activity-collaborators-manager" className="flex flex-col gap-4">
            <div className="flex items-center justify-between gap-3">
                <Input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={t("features.activities.manager.collaborators.filter")}
                    className="w-64"
                />
                {canManage && (
                    <Button size="sm" onClick={() => setInviteOpen(true)}>
                        <Plus className="size-4" />
                        {t("features.activities.manager.collaborators.invite")}
                    </Button>
                )}
            </div>

            <ActivityDataTable
                data={collaborators}
                columns={columns}
                isLoading={isLoading}
                getRowKey={(collaborator) => collaborator.userId}
                search={search}
                filterRow={(collaborator, query) =>
                    (collaborator.user?.getFullName().toLowerCase().includes(query) ?? false) ||
                    (collaborator.user?.email.toLowerCase().includes(query) ?? false)
                }
                emptyIcon={Users}
                emptyLabel={t("features.activities.manager.collaborators.empty")}
                renderCount={(count) =>
                    t("features.activities.manager.collaborators.count", { count })
                }
            />

            <InviteCollaboratorDialog
                activityId={activityId}
                open={inviteOpen}
                onOpenChange={setInviteOpen}
            />
            <AssignRoleDialog
                activityId={activityId}
                collaborator={assignTarget}
                roles={roles}
                open={assignTarget !== null}
                onOpenChange={(open) => {
                    if (!open) setAssignTarget(null);
                }}
            />
        </div>
    );
}

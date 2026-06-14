"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { Pencil, Plus, Trash2 } from "lucide-react";

import { type ActivityRoleModel, deleteActivityRole } from "@workspace/modules/activities";
import { Button } from "@workspace/ui/components/button";
import { Badge } from "@workspace/ui/components/badge";
import { Card } from "@workspace/ui/components/card";

import { useAsyncState } from "@/hooks/use-async-state";
import { activityRolesQueryKey, useActivityRoles } from "../hooks/use-activity-roles";
import { RoleFormDialog } from "./role-form-dialog";

export function ActivityRolesPanel({ activityId }: { activityId: string }) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { roles, isLoading } = useActivityRoles(activityId);
    const { execute } = useAsyncState();
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<ActivityRoleModel | null>(null);

    const openCreate = () => {
        setEditing(null);
        setDialogOpen(true);
    };

    const openEdit = (role: ActivityRoleModel) => {
        setEditing(role);
        setDialogOpen(true);
    };

    const remove = (role: ActivityRoleModel) =>
        execute(() => deleteActivityRole(activityId, role.id), {
            displayError: true,
            onSuccess: () => {
                queryClient.invalidateQueries({ queryKey: activityRolesQueryKey(activityId) });
                toast.success(t("features.activities.manager.roles.deleted"));
            },
        });

    return (
        <div data-slot="activity-roles-panel" className="flex flex-col gap-4">
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm text-muted-foreground">
                    {t("features.activities.manager.roles.description")}
                </p>
                <Button size="sm" onClick={openCreate}>
                    <Plus className="size-4" />
                    {t("features.activities.manager.roles.create")}
                </Button>
            </div>

            {!isLoading && roles.length === 0 ? (
                <p className="py-8 text-center text-sm text-muted-foreground">
                    {t("features.activities.manager.roles.empty")}
                </p>
            ) : (
                <div className="flex flex-col gap-2">
                    {roles.map((role) => (
                        <Card
                            key={role.id}
                            className="flex flex-row items-center justify-between gap-3 p-4"
                        >
                            <div className="min-w-0">
                                <p className="font-medium">{role.name}</p>
                                <div className="mt-1.5 flex flex-wrap gap-1.5">
                                    {role.permissions.length > 0 ? (
                                        role.permissions.map((permission) => (
                                            <Badge key={permission} variant="secondary" size="sm">
                                                {t(
                                                    `features.activities.manager.permissions.${permission}`,
                                                )}
                                            </Badge>
                                        ))
                                    ) : (
                                        <span className="text-xs text-muted-foreground">
                                            {t("features.activities.manager.roles.noPermissions")}
                                        </span>
                                    )}
                                </div>
                            </div>
                            <div className="flex shrink-0 items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => openEdit(role)}
                                    aria-label={t("common.actions.edit")}
                                >
                                    <Pencil className="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => remove(role)}
                                    aria-label={t("common.actions.delete")}
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        </Card>
                    ))}
                </div>
            )}

            <RoleFormDialog
                activityId={activityId}
                role={editing}
                open={dialogOpen}
                onOpenChange={setDialogOpen}
            />
        </div>
    );
}

"use client";

import { useEffect } from "react";
import { useForm, Controller } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";

import {
    activityRoleSchema,
    type ActivityPermission,
    type ActivityRoleInput,
    type ActivityRoleModel,
    ACTIVITY_PERMISSIONS,
    createActivityRole,
    updateActivityRole,
} from "@workspace/modules/activities";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@workspace/ui/components/dialog";
import { Button } from "@workspace/ui/components/button";
import { Checkbox } from "@workspace/ui/components/checkbox";
import { Alert, AlertDescription } from "@workspace/ui/components/alert";

import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";
import { activityRolesQueryKey } from "../hooks/use-activity-roles";

function togglePermission(
    list: ActivityPermission[],
    permission: ActivityPermission,
    checked: boolean,
): ActivityPermission[] {
    return checked ? [...list, permission] : list.filter((value) => value !== permission);
}

type RoleFormDialogProps = {
    activityId: string;
    role?: ActivityRoleModel | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function RoleFormDialog({ activityId, role, open, onOpenChange }: RoleFormDialogProps) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute, isLoading, error } = useAsyncState();

    const { control, handleSubmit, reset, setError } = useForm<ActivityRoleInput>({
        resolver: zodResolver(activityRoleSchema),
        defaultValues: { name: "", permissions: [] },
    });

    useEffect(() => {
        if (open) {
            reset({ name: role?.name ?? "", permissions: role?.permissions ?? [] });
        }
    }, [open, role, reset]);

    const onSubmit = async (data: ActivityRoleInput) => {
        await execute(
            () =>
                role
                    ? updateActivityRole(activityId, role.id, data)
                    : createActivityRole(activityId, data),
            {
                setFieldError: setError,
                onSuccess: () => {
                    queryClient.invalidateQueries({ queryKey: activityRolesQueryKey(activityId) });
                    toast.success(t("features.activities.manager.roles.saved"));
                    onOpenChange(false);
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="role-form-dialog">
                <DialogHeader>
                    <DialogTitle>
                        {role
                            ? t("features.activities.manager.roles.edit")
                            : t("features.activities.manager.roles.create")}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                    <InputController
                        name="name"
                        control={control}
                        label={t("features.activities.manager.roles.name")}
                        placeholder={t("features.activities.manager.roles.namePlaceholder")}
                        isLoading={isLoading}
                    />
                    <Controller
                        control={control}
                        name="permissions"
                        render={({ field }) => (
                            <div className="space-y-2">
                                <p className="text-sm font-medium">
                                    {t("features.activities.manager.roles.permissions")}
                                </p>
                                <div className="grid gap-2">
                                    {ACTIVITY_PERMISSIONS.map((permission) => (
                                        <label
                                            key={permission}
                                            className="flex cursor-pointer items-center gap-2.5 text-sm"
                                        >
                                            <Checkbox
                                                checked={field.value.includes(permission)}
                                                onCheckedChange={(checked) =>
                                                    field.onChange(
                                                        togglePermission(
                                                            field.value,
                                                            permission,
                                                            Boolean(checked),
                                                        ),
                                                    )
                                                }
                                            />
                                            {t(
                                                `features.activities.manager.permissions.${permission}`,
                                            )}
                                        </label>
                                    ))}
                                </div>
                            </div>
                        )}
                    />
                    {error && (
                        <Alert variant="destructive">
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}
                    <DialogFooter>
                        <Button type="submit" disabled={isLoading}>
                            {t("common.actions.save")}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

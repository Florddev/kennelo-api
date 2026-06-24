"use client";

import { useEffect } from "react";
import { useForm, Controller } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";

import {
    assignCollaboratorRoleSchema,
    type AssignCollaboratorRoleInput,
    type ActivityCollaboratorModel,
    type ActivityRoleModel,
    assignCollaboratorRole,
} from "@workspace/modules/activities";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@workspace/ui/components/dialog";
import { Button } from "@workspace/ui/components/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@workspace/ui/components/select";
import { Alert, AlertDescription } from "@workspace/ui/components/alert";

import { useAsyncState } from "@/hooks/use-async-state";
import { activityCollaboratorsQueryKey } from "../hooks/use-activity-collaborators";

type AssignRoleDialogProps = {
    activityId: string;
    collaborator: ActivityCollaboratorModel | null;
    roles: ActivityRoleModel[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function AssignRoleDialog({
    activityId,
    collaborator,
    roles,
    open,
    onOpenChange,
}: AssignRoleDialogProps) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute, isLoading, error } = useAsyncState();

    const { control, handleSubmit, reset } = useForm<AssignCollaboratorRoleInput>({
        resolver: zodResolver(assignCollaboratorRoleSchema),
        defaultValues: { roleId: "" },
    });

    useEffect(() => {
        if (open) {
            reset({ roleId: collaborator?.role?.id ?? "" });
        }
    }, [open, collaborator, reset]);

    const onSubmit = async (data: AssignCollaboratorRoleInput) => {
        if (!collaborator) return;

        await execute(() => assignCollaboratorRole(activityId, collaborator.userId, data), {
            displayError: true,
            onSuccess: () => {
                queryClient.invalidateQueries({
                    queryKey: activityCollaboratorsQueryKey(activityId),
                });
                toast.success(t("features.activities.manager.collaborators.roleAssigned"));
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="assign-role-dialog">
                <DialogHeader>
                    <DialogTitle>
                        {t("features.activities.manager.collaborators.assignRole")}
                    </DialogTitle>
                </DialogHeader>
                {roles.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t("features.activities.manager.collaborators.noRoles")}
                    </p>
                ) : (
                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                        <Controller
                            control={control}
                            name="roleId"
                            render={({ field }) => (
                                <Select value={field.value} onValueChange={field.onChange}>
                                    <SelectTrigger className="w-full">
                                        <SelectValue
                                            placeholder={t(
                                                "features.activities.manager.collaborators.selectRole",
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem key={role.id} value={role.id}>
                                                {role.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
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
                )}
            </DialogContent>
        </Dialog>
    );
}

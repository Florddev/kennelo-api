"use client";

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { Letter } from "@solar-icons/react";

import {
    inviteCollaboratorSchema,
    type InviteCollaboratorInput,
    inviteCollaborator,
} from "@workspace/modules/activities";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@workspace/ui/components/dialog";
import { Button } from "@workspace/ui/components/button";
import { Alert, AlertDescription } from "@workspace/ui/components/alert";

import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";
import { activityCollaboratorsQueryKey } from "../hooks/use-activity-collaborators";

type InviteCollaboratorDialogProps = {
    activityId: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function InviteCollaboratorDialog({
    activityId,
    open,
    onOpenChange,
}: InviteCollaboratorDialogProps) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute, isLoading, error } = useAsyncState();

    const { control, handleSubmit, reset, setError } = useForm<InviteCollaboratorInput>({
        resolver: zodResolver(inviteCollaboratorSchema),
        defaultValues: { email: "" },
    });

    useEffect(() => {
        if (open) {
            reset({ email: "" });
        }
    }, [open, reset]);

    const onSubmit = async (data: InviteCollaboratorInput) => {
        await execute(() => inviteCollaborator(activityId, data), {
            setFieldError: setError,
            onSuccess: () => {
                queryClient.invalidateQueries({
                    queryKey: activityCollaboratorsQueryKey(activityId),
                });
                toast.success(t("features.activities.manager.collaborators.invited"));
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="invite-collaborator-dialog">
                <DialogHeader>
                    <DialogTitle>
                        {t("features.activities.manager.collaborators.invite")}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                    <InputController
                        name="email"
                        type="email"
                        control={control}
                        label={t("common.fields.email")}
                        placeholder={t("common.placeholders.email")}
                        isLoading={isLoading}
                        Icon={Letter}
                    />
                    {error && (
                        <Alert variant="destructive">
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}
                    <DialogFooter>
                        <Button type="submit" disabled={isLoading}>
                            {t("features.activities.manager.collaborators.sendInvite")}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

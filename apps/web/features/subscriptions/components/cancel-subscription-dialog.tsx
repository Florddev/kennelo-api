"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { toast } from "sonner";

import { cancelSubscription } from "@workspace/modules/subscriptions";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@workspace/ui/components/alert-dialog";
import { Button } from "@workspace/ui/components/button";

import { useAsyncState } from "@/hooks/use-async-state";

type CancelSubscriptionDialogProps = {
    activityId: string;
    onSuccess?: () => void;
};

export function CancelSubscriptionDialog({ activityId, onSuccess }: CancelSubscriptionDialogProps) {
    const t = useTranslations("features.subscriptions");
    const { execute, isLoading } = useAsyncState();
    const [isOpen, setIsOpen] = useState(false);

    const submit = () =>
        execute(() => cancelSubscription(activityId), {
            displayError: true,
            onSuccess: () => {
                toast.success(t("cancelDialog.success"));
                onSuccess?.();
                setIsOpen(false);
            },
        });

    return (
        <>
            <Button variant="outline" className="rounded-4xl" onClick={() => setIsOpen(true)}>
                {t("cancelDialog.trigger")}
            </Button>
            <AlertDialog open={isOpen} onOpenChange={setIsOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>{t("cancelDialog.title")}</AlertDialogTitle>
                        <AlertDialogDescription>
                            {t("cancelDialog.description")}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <Button
                            variant="ghost"
                            onClick={() => setIsOpen(false)}
                            disabled={isLoading}
                        >
                            {t("cancelDialog.keep")}
                        </Button>
                        <Button variant="destructive" onClick={submit} disabled={isLoading}>
                            {isLoading ? t("cancelDialog.canceling") : t("cancelDialog.confirm")}
                        </Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}

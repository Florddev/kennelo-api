"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";

import { cancelBooking, type BookingModel } from "@workspace/modules/bookings";
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

export function useClientCancelDialog(booking: BookingModel, onSuccess?: () => void) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute, isLoading } = useAsyncState();
    const [isOpen, setIsOpen] = useState(false);

    const open = () => setIsOpen(true);
    const close = () => setIsOpen(false);

    const submit = () =>
        execute(() => cancelBooking(booking.id), {
            displayError: true,
            onSuccess: () => {
                queryClient.invalidateQueries({ queryKey: ["booking", booking.id] });
                toast.success(t("features.bookings.detail.clientCancelDialog.success"));
                onSuccess?.();
                close();
            },
        });

    const dialog = (
        <AlertDialog open={isOpen} onOpenChange={setIsOpen}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {t("features.bookings.detail.clientCancelDialog.title")}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {t("features.bookings.detail.clientCancelDialog.description")}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <Button variant="ghost" onClick={close} disabled={isLoading}>
                        {t("common.actions.cancel")}
                    </Button>
                    <Button variant="destructive" onClick={submit} disabled={isLoading}>
                        {t("features.bookings.detail.clientCancelDialog.confirm")}
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );

    return { open, isLoading, dialog };
}

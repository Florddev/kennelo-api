"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useQueryClient } from "@tanstack/react-query";

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@workspace/ui/components/dialog";
import { Button } from "@workspace/ui/components/button";
import { Textarea } from "@workspace/ui/components/textarea";

import {
    confirmActivityBooking,
    cancelActivityBooking,
    type BookingModel,
} from "@workspace/modules/bookings";

import { useAsyncState } from "@/hooks/use-async-state";

type BookingActionType = "confirm" | "cancel";

type UseBookingActionDialogOptions = {
    booking: BookingModel;
    action: BookingActionType;
    onSuccess?: () => void;
};

export function useBookingActionDialog({
    booking,
    action,
    onSuccess,
}: UseBookingActionDialogOptions) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute, isLoading } = useAsyncState();
    const [isOpen, setIsOpen] = useState(false);
    const [message, setMessage] = useState("");

    const isConfirm = action === "confirm";
    const suggestions = t.raw(
        isConfirm
            ? "features.bookings.detail.confirmDialog.suggestions"
            : "features.bookings.detail.cancelDialog.suggestions",
    ) as string[];

    const open = () => {
        setMessage("");
        setIsOpen(true);
    };

    const close = () => setIsOpen(false);

    const submit = () =>
        execute(
            () =>
                isConfirm
                    ? confirmActivityBooking(booking.activityId, booking.id, message || undefined)
                    : cancelActivityBooking(booking.activityId, booking.id, message || undefined),
            {
                onSuccess: () => {
                    queryClient.invalidateQueries({ queryKey: ["booking", booking.id] });
                    onSuccess?.();
                    close();
                },
                displayError: true,
            },
        );

    const dialog = (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t(
                            isConfirm
                                ? "features.bookings.detail.confirmDialog.title"
                                : "features.bookings.detail.cancelDialog.title",
                        )}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            isConfirm
                                ? "features.bookings.detail.confirmDialog.description"
                                : "features.bookings.detail.cancelDialog.description",
                        )}
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-col gap-3">
                    <Textarea
                        value={message}
                        onChange={(e) => setMessage(e.target.value)}
                        placeholder={t(
                            isConfirm
                                ? "features.bookings.detail.confirmDialog.messagePlaceholder"
                                : "features.bookings.detail.cancelDialog.messagePlaceholder",
                        )}
                        rows={3}
                        className="resize-none"
                    />

                    <div className="flex flex-col gap-1.5">
                        {suggestions.map((suggestion, i) => (
                            <button
                                key={i}
                                type="button"
                                onClick={() => setMessage(suggestion)}
                                className="text-xs px-3 py-2 rounded-2xl border border-border/50 bg-muted/40 hover:bg-muted text-muted-foreground hover:text-foreground transition-colors text-start line-clamp-2"
                            >
                                {suggestion}
                            </button>
                        ))}
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="ghost" onClick={close} disabled={isLoading}>
                        {t("common.actions.cancel")}
                    </Button>
                    <Button
                        onClick={submit}
                        disabled={isLoading}
                        variant={isConfirm ? "default" : "destructive"}
                    >
                        {t(
                            isConfirm
                                ? "features.bookings.detail.confirmBooking"
                                : "features.bookings.detail.cancelAsHost",
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );

    return { open, close, isOpen, isLoading, dialog };
}

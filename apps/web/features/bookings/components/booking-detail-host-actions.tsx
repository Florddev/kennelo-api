"use client";

import { useTranslations } from "next-intl";
import { CheckCircle, CheckSquare, CloseCircle } from "@solar-icons/react";

import { Button } from "@workspace/ui/components/button";

import { completeActivityBooking, type BookingModel } from "@workspace/modules/bookings";

import { useAsyncState } from "@/hooks/use-async-state";
import { useQueryClient } from "@tanstack/react-query";
import { useBookingActionDialog } from "../hooks/use-booking-action-dialog";

export function BookingDetailHostActions({ booking }: { booking: BookingModel }) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const { execute, isLoading } = useAsyncState();

    const canConfirm = booking.isPending();
    const canComplete = booking.isConfirmed();
    const canCancel = booking.isPending() || booking.isConfirmed();

    const { open: openConfirm, dialog: confirmDialog } = useBookingActionDialog({
        booking,
        action: "confirm",
    });

    const { open: openCancel, dialog: cancelDialog } = useBookingActionDialog({
        booking,
        action: "cancel",
    });

    const handleComplete = () =>
        execute(() => completeActivityBooking(booking.activityId, booking.id), {
            onSuccess: () => queryClient.invalidateQueries({ queryKey: ["booking", booking.id] }),
            displayError: true,
        });

    if (!canConfirm && !canComplete && !canCancel) return null;

    return (
        <div className="flex flex-col gap-3">
            <h2 className="text-xl font-semibold">
                {t("features.bookings.detail.managementSection")}
            </h2>

            <div className="flex flex-col gap-2">
                {canConfirm && (
                    <Button onClick={openConfirm} className="w-full">
                        <CheckCircle />
                        {t("features.bookings.detail.confirmBooking")}
                    </Button>
                )}

                {canComplete && (
                    <Button
                        onClick={handleComplete}
                        disabled={isLoading}
                        variant="secondary"
                        className="w-full"
                    >
                        <CheckSquare />
                        {t("features.bookings.detail.completeBooking")}
                    </Button>
                )}

                {canCancel && (
                    <Button variant="destructive" onClick={openCancel} className="w-full">
                        <CloseCircle />
                        {t("features.bookings.detail.cancelAsHost")}
                    </Button>
                )}
            </div>

            {confirmDialog}
            {cancelDialog}
        </div>
    );
}

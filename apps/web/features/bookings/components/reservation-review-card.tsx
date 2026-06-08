"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Calendar, PawPrint, User } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent } from "@workspace/ui/components/card";
import { Badge } from "@workspace/ui/components/badge";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@workspace/ui/components/alert-dialog";
import { formatAmount } from "@workspace/common";
import {
    confirmActivityBooking,
    cancelActivityBooking,
    type BookingModel,
} from "@workspace/modules/bookings";

type ReservationReviewCardProps = {
    booking: BookingModel;
    activityId: string;
};

export function ReservationReviewCard({ booking, activityId }: ReservationReviewCardProps) {
    const t = useTranslations();
    const queryClient = useQueryClient();
    const [isRejectOpen, setIsRejectOpen] = useState(false);

    const accept = useMutation({
        mutationFn: () => confirmActivityBooking(activityId, booking.id),
        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: ["activity-bookings", activityId],
            });
            toast.success(t("features.bookings.review.acceptSuccess"));
        },
        onError: (err: Error) =>
            toast.error(t("features.bookings.review.acceptFailed"), { description: err.message }),
    });

    const reject = useMutation({
        mutationFn: () => cancelActivityBooking(activityId, booking.id),
        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: ["activity-bookings", activityId],
            });
            toast.success(t("features.bookings.review.refundIssued"));
        },
        onError: (err: Error) =>
            toast.error(t("features.bookings.review.refundFailed"), { description: err.message }),
    });

    const customerName = booking.user
        ? `${booking.user.firstName ?? ""} ${booking.user.lastName ?? ""}`.trim() ||
          booking.user.email
        : t("features.bookings.review.unknownCustomer");

    const petsLabel = (booking.pets ?? []).map((p) => p.name).join(", ");

    let paymentBadge: { label: string; variant: "default" | "secondary" };
    if (booking.isRefunded()) {
        paymentBadge = { label: t("features.bookings.status.refunded"), variant: "secondary" };
    } else if (booking.paymentStatus === "succeeded") {
        paymentBadge = { label: t("features.bookings.review.paymentHeld"), variant: "default" };
    } else {
        paymentBadge = { label: booking.paymentStatus ?? "pending", variant: "secondary" };
    }

    return (
        <Card data-slot="reservation-review-card" className="rounded-2xl">
            <CardContent className="flex flex-col gap-3 p-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2">
                            <User className="size-4 text-muted-foreground" />
                            <p className="text-sm font-semibold text-foreground">{customerName}</p>
                        </div>
                        <div className="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Calendar className="size-3.5" />
                            <span>
                                {booking.checkInDate} → {booking.checkOutDate}
                            </span>
                        </div>
                        {petsLabel && (
                            <div className="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                                <PawPrint className="size-3.5" />
                                <span>{petsLabel}</span>
                            </div>
                        )}
                    </div>
                    <div className="flex flex-col items-end gap-1">
                        <p className="text-base font-semibold text-foreground">
                            {formatAmount(Number(booking.totalPrice))} €
                        </p>
                        <Badge variant={paymentBadge.variant} className="text-[10px]">
                            {paymentBadge.label}
                        </Badge>
                    </div>
                </div>

                {booking.specialRequests && (
                    <p className="rounded-2xl bg-muted/40 p-3 text-xs text-foreground">
                        {booking.specialRequests}
                    </p>
                )}

                {booking.status === "pending" && (
                    <div className="flex items-center justify-end gap-2 pt-1">
                        <AlertDialog open={isRejectOpen} onOpenChange={setIsRejectOpen}>
                            <AlertDialogTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={reject.isPending || accept.isPending}
                                    className="rounded-4xl text-destructive hover:text-destructive"
                                >
                                    {t("features.bookings.review.reject")}
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>
                                        {t("features.bookings.review.rejectConfirmTitle")}
                                    </AlertDialogTitle>
                                    <AlertDialogDescription>
                                        {t("features.bookings.review.rejectConfirmDescription")}
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>
                                        {t("common.actions.cancel")}
                                    </AlertDialogCancel>
                                    <AlertDialogAction
                                        variant="destructive"
                                        onClick={() => reject.mutate()}
                                    >
                                        {t("features.bookings.review.reject")}
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                        <Button
                            size="sm"
                            onClick={() => accept.mutate()}
                            disabled={accept.isPending || reject.isPending}
                            className="rounded-4xl"
                        >
                            {accept.isPending
                                ? t("common.actions.loading")
                                : t("features.bookings.review.accept")}
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

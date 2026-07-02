"use client";

import { useLocale, useTranslations } from "next-intl";
import { Wallet } from "@solar-icons/react";

import { Badge } from "@workspace/ui/components/badge";
import { Separator } from "@workspace/ui/components/separator";
import type { BookingModel } from "@workspace/modules/bookings";

export function BookingDetailPrice({
    booking,
    isHost,
}: {
    booking: BookingModel;
    isHost: boolean;
}) {
    const t = useTranslations();
    const locale = useLocale();

    const formatDate = (dateStr: string) =>
        new Date(dateStr).toLocaleDateString(locale, {
            day: "2-digit",
            month: "long",
            year: "numeric",
        });

    return (
        <div className="flex flex-col gap-2">
            <h2 className="text-xl font-semibold">
                {t("features.bookings.detail.priceBreakdown")}
            </h2>
            <div className="bg-muted/50 rounded-2xl p-4 flex flex-col gap-2">
                <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">
                        {t("features.bookings.detail.activityPrice")}
                    </span>
                    <span className="text-sm">
                        {(Number(booking.totalPrice) - Number(booking.serviceFee)).toFixed(2)} €
                    </span>
                </div>
                <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">
                        {t("features.bookings.detail.serviceFee")}
                    </span>
                    <span className="text-sm">{booking.serviceFee} €</span>
                </div>
                <Separator className="opacity-30" />
                <div className="flex items-center justify-between">
                    <span className="text-sm font-semibold">
                        {t("features.bookings.detail.totalPaid")}
                    </span>
                    <span className="text-sm font-semibold">{booking.totalPrice} €</span>
                </div>
                {isHost && (
                    <>
                        <Separator className="opacity-30" />
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-muted-foreground">
                                {t("features.bookings.detail.platformFee")}
                            </span>
                            <span className="text-sm">-{booking.platformFee} €</span>
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-semibold">
                                {t("features.bookings.detail.netPayout")}
                            </span>
                            <span className="text-sm font-semibold">
                                {booking.activityAmount} €
                            </span>
                        </div>
                    </>
                )}
                {booking.paymentStatus && (
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Wallet className="size-4 text-muted-foreground" />
                            <span className="text-sm text-muted-foreground">
                                {t("features.bookings.detail.paymentStatus")}
                            </span>
                        </div>
                        <Badge variant="outline">
                            {t(`features.bookings.detail.paymentStatuses.${booking.paymentStatus}`)}
                        </Badge>
                    </div>
                )}
                {booking.paidAt && (
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">
                            {t("features.bookings.detail.paidOn")}
                        </span>
                        <span className="text-sm text-muted-foreground">
                            {formatDate(booking.paidAt)}
                        </span>
                    </div>
                )}
            </div>
        </div>
    );
}

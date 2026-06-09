"use client";

import { useTranslations, useLocale } from "next-intl";
import { CalendarMinimalistic, MapPoint, Bill } from "@solar-icons/react";

import type { BookingModel } from "@workspace/modules/bookings";
import type { AddressModel } from "@workspace/modules/address";

import { BookingStatusBadge } from "./booking-status-badge";

export function BookingDetailIdentity({
    booking,
    activityName,
    address,
}: {
    booking: BookingModel;
    activityName?: string | null;
    address: AddressModel | null;
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
        <div className="p-4 pb-2 flex flex-col gap-2">
            <div className="flex items-start justify-between gap-3">
                <h1 className="text-2xl font-bold tracking-tight leading-tight">
                    {activityName ?? t("features.bookings.detail.title")}
                </h1>
                <BookingStatusBadge status={booking.status} />
            </div>

            <div className="flex flex-col gap-1">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <CalendarMinimalistic className="size-3.5 shrink-0" />
                    <span>
                        {t("features.bookings.detail.datesValue", {
                            from: formatDate(booking.checkInDate),
                            to: formatDate(booking.checkOutDate),
                        })}
                    </span>
                </div>

                {address && (
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <MapPoint className="size-3.5 shrink-0" />
                        <span>
                            {address.city}
                            {address.postalCode ? `, ${address.postalCode}` : ""}
                        </span>
                    </div>
                )}

                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Bill className="size-3.5 shrink-0" />
                    <span>#{booking.id.slice(0, 8).toUpperCase()}</span>
                </div>
            </div>
        </div>
    );
}

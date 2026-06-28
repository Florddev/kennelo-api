"use client";

import { useTranslations } from "next-intl";
import { Buildings, Calendar } from "@solar-icons/react";
import { PawPrint } from "lucide-react";

import { computeNights, fromApiDate } from "@workspace/common";
import type { BookingModel } from "@workspace/modules/bookings";

import { BookingStatusBadge } from "@/features/bookings";

export function CurrentBookingCard({ booking }: { booking: BookingModel }) {
    const t = useTranslations("features.hosting-scan.currentBooking");

    const nights = computeNights(
        fromApiDate(booking.checkInDate),
        fromApiDate(booking.checkOutDate),
    );

    return (
        <div className="flex flex-col gap-4 rounded-4xl border bg-card p-6">
            <div className="flex items-center justify-between gap-2">
                <h2 className="text-xl font-semibold">{t("title")}</h2>
                <BookingStatusBadge status={booking.status} />
            </div>

            {booking.activity && (
                <div className="flex items-center gap-2.5">
                    <div className="flex size-9 items-center justify-center rounded-2xl bg-muted">
                        <Buildings className="size-5 text-primary" />
                    </div>
                    <div className="flex flex-col">
                        <span className="text-xs text-muted-foreground">{t("activity")}</span>
                        <span className="text-sm font-medium">{booking.activity.name}</span>
                    </div>
                </div>
            )}

            <div className="flex items-center gap-2.5">
                <div className="flex size-9 items-center justify-center rounded-2xl bg-muted">
                    <Calendar className="size-5 text-primary" />
                </div>
                <div className="flex flex-col">
                    <span className="text-xs text-muted-foreground">{t("dates")}</span>
                    <span className="text-sm font-medium">
                        {booking.checkInDate} → {booking.checkOutDate}
                    </span>
                    <span className="text-xs text-muted-foreground">
                        {t("nights", { count: nights })}
                    </span>
                </div>
            </div>

            {booking.pets && booking.pets.length > 0 && (
                <div className="flex items-center gap-2.5">
                    <div className="flex size-9 items-center justify-center rounded-2xl bg-muted">
                        <PawPrint className="size-5 text-primary" />
                    </div>
                    <div className="flex flex-col">
                        <span className="text-xs text-muted-foreground">{t("pets")}</span>
                        <span className="text-sm font-medium">
                            {booking.pets.map((bookingPet) => bookingPet.name).join(", ")}
                        </span>
                    </div>
                </div>
            )}

            {booking.specialRequests && (
                <div className="flex flex-col gap-1 rounded-2xl bg-muted/50 p-3">
                    <span className="text-xs text-muted-foreground">{t("specialRequests")}</span>
                    <p className="text-sm">{booking.specialRequests}</p>
                </div>
            )}
        </div>
    );
}

"use client";

import { useTranslations } from "next-intl";

import type { BookingModel } from "@workspace/modules/bookings";

import { CurrentBookingCard } from "./current-booking-card";

export function ScannedPetCurrentBooking({ booking }: { booking: BookingModel | null }) {
    const t = useTranslations("features.hosting-scan.currentBooking");

    if (booking) {
        return <CurrentBookingCard booking={booking} />;
    }

    return (
        <div className="flex flex-col gap-3">
            <h2 className="text-xl font-semibold">{t("title")}</h2>
            <p className="rounded-2xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                {t("none")}
            </p>
        </div>
    );
}

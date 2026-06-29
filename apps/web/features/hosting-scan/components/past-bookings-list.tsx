"use client";

import { useTranslations } from "next-intl";

import type { BookingModel } from "@workspace/modules/bookings";

export function PastBookingsList({ bookings }: { bookings: BookingModel[] }) {
    const t = useTranslations("features.hosting-scan.pastBookings");

    return (
        <div className="flex flex-col gap-3">
            <div className="flex items-center justify-between gap-2">
                <h2 className="text-xl font-semibold">{t("title")}</h2>
                {bookings.length > 0 && (
                    <span className="text-sm text-muted-foreground">
                        {t("count", { count: bookings.length })}
                    </span>
                )}
            </div>

            {bookings.length === 0 ? (
                <p className="rounded-2xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                    {t("empty")}
                </p>
            ) : (
                <div className="flex flex-col gap-2">
                    {bookings.map((booking) => (
                        <div
                            key={booking.id}
                            className="flex items-center justify-between gap-3 rounded-2xl border bg-card p-3"
                        >
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate text-sm font-medium">
                                    {booking.activity?.name}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    {booking.checkInDate} → {booking.checkOutDate}
                                </span>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

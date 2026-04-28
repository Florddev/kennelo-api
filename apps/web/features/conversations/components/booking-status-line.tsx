"use client";

import { useLocale, useTranslations } from "next-intl";
import { BookingThreadModel } from "@workspace/modules/conversations";
import { formatDateRangeShort, isWithinOneMonthAfter } from "@workspace/common";
import { StatusDot } from "./status-dot";

export function BookingStatusLine({
    bookingThreads,
}: {
    bookingThreads: BookingThreadModel[] | null;
}) {
    const locale = useLocale();
    const t = useTranslations();

    const latestBooking =
        bookingThreads
            ?.map((thread) => thread.booking)
            .filter((b): b is NonNullable<typeof b> => b !== null)
            .sort(
                (a, b) => new Date(b.checkOutDate).getTime() - new Date(a.checkOutDate).getTime(),
            )[0] ?? null;

    if (!latestBooking) return null;

    const showStatus =
        isWithinOneMonthAfter(latestBooking.checkOutDate) &&
        (latestBooking.isConfirmed() || latestBooking.isCancelled());

    return (
        <div className="flex items-center gap-1.5 mt-0.5">
            {showStatus && (
                <>
                    <StatusDot isCancelled={latestBooking.isCancelled()} showAnimation={false} />
                    <span className="text-xs text-muted-foreground">
                        {latestBooking.isConfirmed()
                            ? t("features.conversations.lastBooking.confirmed")
                            : t("features.conversations.lastBooking.cancelled")}
                    </span>
                    <span className="text-xs text-muted-foreground">·</span>
                </>
            )}
            <span className="text-xs text-muted-foreground truncate">
                {formatDateRangeShort(
                    latestBooking.checkInDate,
                    latestBooking.checkOutDate,
                    locale,
                )}
            </span>
        </div>
    );
}

"use client";

import { useTranslations } from "next-intl";
import { ChatRoundLine } from "@solar-icons/react";
import { Button } from "@workspace/ui/components/button";
import { computeNights, formatDay } from "@workspace/common";
import type { PriceCalendar } from "@workspace/modules/activities";
import type { DateRange } from "react-day-picker";

import { minAvailablePrice, totalPriceForRange } from "../lib/pricing";

type HostBookingBarProps = {
    priceMap: PriceCalendar;
    dateRange: DateRange | undefined;
    onBook: () => void;
    onContact?: () => void;
    isContactPending?: boolean;
};

export function HostBookingBar({
    priceMap,
    dateRange,
    onBook,
    onContact,
    isContactPending,
}: HostBookingBarProps) {
    const t = useTranslations();

    return (
        <div className="border-t bg-card px-4 py-3">
            <div className="container mx-auto flex h-full items-center justify-between gap-4">
                <div className="flex min-w-0 flex-col gap-1">
                    <PriceDisplay priceMap={priceMap} dateRange={dateRange} />
                </div>
                <div className="flex shrink-0 items-center gap-2">
                    {onContact && (
                        <Button
                            size="icon-lg"
                            variant="outline"
                            onClick={onContact}
                            disabled={isContactPending}
                            className="rounded-full"
                            aria-label={t("features.conversations.contactHost")}
                        >
                            <ChatRoundLine />
                        </Button>
                    )}
                    <Button
                        size="lg"
                        onClick={onBook}
                        className="rounded-full bg-foreground px-10 text-base font-medium text-background hover:bg-foreground/90"
                    >
                        {t("features.host.detail.book")}
                    </Button>
                </div>
            </div>
        </div>
    );
}

export function PriceDisplay({
    priceMap,
    dateRange,
}: {
    priceMap: PriceCalendar;
    dateRange: DateRange | undefined;
}) {
    const t = useTranslations();
    const fromPrice = minAvailablePrice(priceMap);
    const nights = computeNights(dateRange?.from, dateRange?.to);
    const hasRange = Boolean(nights > 0 && dateRange?.from && dateRange?.to);

    if (hasRange && dateRange?.from && dateRange?.to) {
        const total = totalPriceForRange(priceMap, dateRange.from, dateRange.to);
        const locale = typeof navigator !== "undefined" ? navigator.language : "fr-FR";
        return (
            <>
                <p className="text-xs text-muted-foreground">
                    {t("features.host.detail.stayNights", { count: nights })}
                    {" · "}
                    {formatDay(dateRange.from, locale)}-{formatDay(dateRange.to, locale)}
                </p>
                {total !== null ? (
                    <p className="flex items-baseline gap-1 text-slate-900">
                        <span className="text-xl font-bold underline">{Math.round(total)} €</span>
                        <span className="text-sm text-muted-foreground">
                            {t("features.host.detail.totalSuffix")}
                        </span>
                    </p>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        {t("features.host.detail.priceUnavailable")}
                    </p>
                )}
            </>
        );
    }

    if (fromPrice === null) {
        return (
            <p className="text-sm text-muted-foreground">
                {t("features.host.detail.priceUnavailable")}
            </p>
        );
    }

    return (
        <div className="text-slate-900 flex flex-col">
            <span className="text-sm font-medium text-muted-foreground">
                {t("features.host.detail.fromPriceLabel")}
            </span>
            <div className="flex *:items-baseline gap-1 items-end">
                <span className="text-xl font-bold">{Math.round(fromPrice)} €</span>
                <span className="text-sm font-medium text-muted-foreground">
                    {t("features.host.detail.perNight")}
                </span>
            </div>
        </div>
    );
}

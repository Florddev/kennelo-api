"use client";

import { useTranslations } from "next-intl";
import { ChatRoundLine } from "@solar-icons/react";
import { Button } from "@workspace/ui/components/button";
import { computeNights, formatAmount, formatDay } from "@workspace/common";
import type { DateRange } from "react-day-picker";

type HostBookingBarProps = {
    pricePerNight: number | null;
    dateRange: DateRange | undefined;
    canBook: boolean;
    onBook: () => void;
    onContact?: () => void;
    isContactPending?: boolean;
};

export function HostBookingBar({
    pricePerNight,
    dateRange,
    canBook,
    onBook,
    onContact,
    isContactPending,
}: HostBookingBarProps) {
    const t = useTranslations();
    const nights = computeNights(dateRange?.from, dateRange?.to);
    const hasRange = Boolean(nights > 0 && dateRange?.from && dateRange?.to);

    return (
        <div className="border-t bg-card px-4 py-3">
            <div className="container mx-auto flex h-full items-center justify-between gap-4">
                <div className="flex min-w-0 flex-col gap-1">
                    <PriceDisplay
                        pricePerNight={pricePerNight}
                        dateRange={dateRange}
                        nights={nights}
                        hasRange={hasRange}
                    />
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
                        disabled={!canBook}
                        className="rounded-full bg-foreground px-10 text-base font-medium text-background hover:bg-foreground/90 disabled:opacity-50"
                    >
                        {t("features.host.detail.book")}
                    </Button>
                </div>
            </div>
        </div>
    );
}

function PriceDisplay({
    pricePerNight,
    dateRange,
    nights,
    hasRange,
}: {
    pricePerNight: number | null;
    dateRange: DateRange | undefined;
    nights: number;
    hasRange: boolean;
}) {
    const t = useTranslations();

    if (pricePerNight === null) {
        return (
            <p className="text-sm text-muted-foreground">
                {t("features.host.detail.priceUnavailable")}
            </p>
        );
    }

    if (hasRange && dateRange?.from && dateRange?.to) {
        const locale = typeof navigator !== "undefined" ? navigator.language : "fr-FR";
        const total = pricePerNight * nights;
        return (
            <>
                <p className="text-xs text-muted-foreground">
                    {t("features.host.detail.stayNights", { count: nights })}
                    {" · "}
                    {formatDay(dateRange.from, locale)}-{formatDay(dateRange.to, locale)}
                </p>
                <p className="flex items-baseline gap-1 text-slate-900">
                    <span className="text-xl font-bold underline">{formatAmount(total)} €</span>
                    <span className="text-sm text-muted-foreground">
                        {t("features.host.detail.totalSuffix")}
                    </span>
                </p>
            </>
        );
    }

    return (
        <p className="text-slate-900">
            <span className="text-sm font-medium text-muted-foreground">
                {t("features.host.detail.fromPriceLabel")}
            </span>
            <span className="ms-1 text-xl font-bold">{formatAmount(pricePerNight)} €</span>
            <span className="text-sm font-medium text-muted-foreground">
                {t("features.host.detail.perNight")}
            </span>
        </p>
    );
}

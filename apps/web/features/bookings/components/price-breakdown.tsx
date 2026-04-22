import { useTranslations } from "next-intl";
import { Separator } from "@workspace/ui/components/separator";
import { formatAmount } from "@workspace/common";

import type { BookingTotals } from "../lib/pricing";

type PriceBreakdownProps = {
    totals: BookingTotals;
};

export function PriceBreakdown({ totals }: PriceBreakdownProps) {
    const t = useTranslations();

    return (
        <div data-slot="price-breakdown" className="flex flex-col gap-3">
            <div className="flex items-center justify-between text-sm">
                <span className="text-foreground underline">
                    {t("features.bookings.checkout.priceNights", {
                        price: formatAmount(totals.pricePerNight),
                        count: totals.nights,
                    })}
                </span>
                <span className="text-foreground">{formatAmount(totals.total)} €</span>
            </div>
            <Separator />
            <div className="flex items-center justify-between text-base font-semibold">
                <span>{t("features.bookings.checkout.totalCurrency")}</span>
                <span>{formatAmount(totals.total)} €</span>
            </div>
        </div>
    );
}

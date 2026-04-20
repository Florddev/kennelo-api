import { useTranslations } from "next-intl";
import { Separator } from "@workspace/ui/components/separator";

import type { PriceBreakdown as PriceBreakdownType } from "../lib/pricing";

type PriceBreakdownProps = {
    breakdown: PriceBreakdownType;
};

export function PriceBreakdown({ breakdown }: PriceBreakdownProps) {
    const t = useTranslations();

    return (
        <div data-slot="price-breakdown" className="flex flex-col gap-3">
            <div className="flex items-center justify-between text-sm">
                <span className="text-foreground underline">
                    {t("features.bookings.checkout.priceNights", {
                        price: breakdown.pricePerNight,
                        count: breakdown.nights,
                    })}
                </span>
                <span className="text-foreground">{breakdown.subtotal} €</span>
            </div>
            <div className="flex items-center justify-between text-sm">
                <span className="text-foreground underline">
                    {t("features.bookings.checkout.serviceFee")}
                </span>
                <span className="text-foreground">{breakdown.serviceFee} €</span>
            </div>
            <Separator />
            <div className="flex items-center justify-between text-base font-semibold">
                <span>{t("features.bookings.checkout.totalCurrency")}</span>
                <span>{breakdown.total} €</span>
            </div>
        </div>
    );
}

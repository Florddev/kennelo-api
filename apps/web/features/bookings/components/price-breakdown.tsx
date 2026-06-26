import { useTranslations } from "next-intl";
import { Separator } from "@workspace/ui/components/separator";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { formatAmount } from "@workspace/common";
import type { BookingQuoteModel } from "@workspace/modules/bookings";

type PriceBreakdownProps = {
    quote: BookingQuoteModel | null;
    isLoading: boolean;
};

export function PriceBreakdown({ quote, isLoading }: PriceBreakdownProps) {
    const t = useTranslations();

    if (isLoading) {
        return (
            <div data-slot="price-breakdown" className="flex flex-col gap-3">
                <Skeleton className="h-5 w-full" />
                <Skeleton className="h-5 w-full" />
                <Separator />
                <Skeleton className="h-6 w-full" />
            </div>
        );
    }

    if (!quote) {
        return (
            <p data-slot="price-breakdown" className="text-sm text-muted-foreground">
                {t("features.bookings.checkout.selectDatesAndPets")}
            </p>
        );
    }

    return (
        <div data-slot="price-breakdown" className="flex flex-col gap-3">
            <div className="flex items-center justify-between text-sm">
                <span className="text-foreground">
                    {t("features.bookings.checkout.accommodation", { count: quote.nights })}
                </span>
                <span className="text-foreground">{formatAmount(quote.basePrice)} €</span>
            </div>
            <div className="flex items-center justify-between text-sm">
                <span className="text-foreground">
                    {t("features.bookings.checkout.serviceFee", {
                        percent: quote.serviceFeePercent,
                    })}
                </span>
                <span className="text-foreground">{formatAmount(quote.serviceFee)} €</span>
            </div>
            <Separator />
            <div className="flex items-center justify-between text-base font-semibold">
                <span>{t("features.bookings.checkout.totalCurrency")}</span>
                <span>{formatAmount(quote.totalPrice)} €</span>
            </div>
        </div>
    );
}

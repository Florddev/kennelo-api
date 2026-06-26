import { useTranslations } from "next-intl";
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
            <div data-slot="price-breakdown" className="flex flex-col gap-2">
                <Skeleton className="h-5 w-full" />
                <Skeleton className="h-5 w-full" />
                {/* <Separator /> */}
                <Skeleton className="h-6 w-full mt-3" />
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
            {quote.pets.map((pet) => (
                <div key={pet.id} className="flex items-center justify-between gap-3 text-sm">
                    <div className="flex flex-col">
                        <span className="text-foreground">{pet.name}</span>
                        <span className="text-xs text-muted-foreground">
                            {t("features.bookings.checkout.priceNights", {
                                price: formatAmount(pet.pricePerNight),
                                count: pet.numberOfNights,
                            })}
                        </span>
                    </div>
                    <span className="text-foreground">{formatAmount(pet.subtotal)} €</span>
                </div>
            ))}
            <div className="flex items-center justify-between text-sm">
                <span className="text-foreground">
                    {t("features.bookings.checkout.serviceFee", {
                        percent: quote.serviceFeePercent,
                    })}
                </span>
                <span className="text-foreground">{formatAmount(quote.serviceFee)} €</span>
            </div>
            {/* <Separator /> */}
            <div className="flex items-center justify-between text-base font-semibold mt-3">
                <span>{t("features.bookings.checkout.totalCurrency")}</span>
                <span>{formatAmount(quote.totalPrice)} €</span>
            </div>
        </div>
    );
}

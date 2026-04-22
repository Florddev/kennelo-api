import { useTranslations } from "next-intl";

import { BookingInfoRow } from "./booking-info-row";

type BookingTripSectionProps = {
    datesLabel: string;
    petsCountLabel: string;
};

export function BookingTripSection({
    datesLabel,
    petsCountLabel,
}: BookingTripSectionProps) {
    const t = useTranslations();
    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.bookings.checkout.tripSection")}
            </h2>
            <BookingInfoRow
                label={t("features.bookings.checkout.datesLabel")}
                value={datesLabel}
            />
            <BookingInfoRow
                label={t("features.bookings.checkout.petsLabel")}
                value={petsCountLabel}
            />
        </section>
    );
}

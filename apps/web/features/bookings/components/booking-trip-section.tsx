"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { ChevronDown, Pencil } from "lucide-react";
import { cn } from "@workspace/ui/lib/utils";
import type { AvailabilityModel, PriceCalendar } from "@workspace/modules/activities";
import type { DateRange } from "react-day-picker";

import { AvailabilityCalendar } from "@/features/host";

import { BookingInfoRow } from "./booking-info-row";

type BookingTripSectionProps = {
    datesLabel: string | null;
    petsCountLabel: string;
    dateRange: DateRange | undefined;
    onDateRangeChange: (range: DateRange | undefined) => void;
    availabilities: AvailabilityModel[];
    priceMap: PriceCalendar;
};

export function BookingTripSection({
    datesLabel,
    petsCountLabel,
    dateRange,
    onDateRangeChange,
    availabilities,
    priceMap,
}: BookingTripSectionProps) {
    const t = useTranslations();
    const [isEditingDates, setIsEditingDates] = useState(!datesLabel);

    return (
        <section data-slot="booking-trip-section" className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.bookings.checkout.tripSection")}
            </h2>

            <button
                type="button"
                onClick={() => setIsEditingDates((value) => !value)}
                className="flex items-center justify-between gap-2 text-start"
            >
                <span className="text-sm font-medium text-foreground">
                    {t("features.bookings.checkout.datesLabel")}
                </span>
                <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                    {datesLabel ?? t("features.bookings.checkout.selectDates")}
                    {datesLabel ? (
                        <Pencil className="size-3.5" />
                    ) : (
                        <ChevronDown
                            className={cn(
                                "size-4 transition-transform",
                                isEditingDates && "rotate-180",
                            )}
                        />
                    )}
                </span>
            </button>

            {isEditingDates && (
                <div className="rounded-2xl border p-1">
                    <AvailabilityCalendar
                        dateRange={dateRange}
                        onDateRangeChange={onDateRangeChange}
                        availabilities={availabilities}
                        priceMap={priceMap}
                    />
                </div>
            )}

            <BookingInfoRow
                label={t("features.bookings.checkout.petsLabel")}
                value={petsCountLabel}
            />
        </section>
    );
}

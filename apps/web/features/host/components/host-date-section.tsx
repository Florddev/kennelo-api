"use client";

import { useTranslations } from "next-intl";
import type { AvailabilityModel, PriceCalendar } from "@workspace/modules/activities";
import type { DateRange } from "react-day-picker";

import { AvailabilityCalendar } from "./availability-calendar";

type HostDateSectionProps = {
    dateRange: DateRange | undefined;
    onDateRangeChange: (range: DateRange | undefined) => void;
    availabilities: AvailabilityModel[];
    priceMap: PriceCalendar;
};

export function HostDateSection({
    dateRange,
    onDateRangeChange,
    availabilities,
    priceMap,
}: HostDateSectionProps) {
    const t = useTranslations();

    return (
        <section data-slot="host-date-section" className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.host.detail.selectDate")}
            </h2>
            <p className="text-sm text-foreground">
                {t("features.host.detail.selectDateDescription")}
            </p>
            <AvailabilityCalendar
                dateRange={dateRange}
                onDateRangeChange={onDateRangeChange}
                availabilities={availabilities}
                priceMap={priceMap}
            />
            {dateRange && (
                <button
                    type="button"
                    className="self-start px-4 text-sm font-medium underline"
                    onClick={() => onDateRangeChange(undefined)}
                >
                    {t("features.host.detail.clearDates")}
                </button>
            )}
        </section>
    );
}

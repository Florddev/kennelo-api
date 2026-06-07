"use client";

import { useCallback, useMemo } from "react";
import { useTranslations } from "next-intl";
import { Calendar } from "@workspace/ui/components/calendar";
import type { AvailabilityModel } from "@workspace/modules/activities";
import type { DateRange } from "react-day-picker";

import { isDateDisabledForBooking } from "../lib/availability-helpers";

type HostDateSectionProps = {
    dateRange: DateRange | undefined;
    onDateRangeChange: (range: DateRange | undefined) => void;
    availabilities: AvailabilityModel[];
};

export function HostDateSection({
    dateRange,
    onDateRangeChange,
    availabilities,
}: HostDateSectionProps) {
    const t = useTranslations();
    const today = useMemo(() => {
        const date = new Date();
        date.setHours(0, 0, 0, 0);
        return date;
    }, []);

    const disabledMatcher = useCallback(
        (date: Date) => isDateDisabledForBooking(date, availabilities, today),
        [availabilities, today],
    );

    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.host.detail.selectDate")}
            </h2>
            <p className="text-sm text-foreground">
                {t("features.host.detail.selectDateDescription")}
            </p>
            <div className="rounded-2xl border">
                <Calendar
                    mode="range"
                    selected={dateRange}
                    onSelect={onDateRangeChange}
                    disabled={disabledMatcher}
                    excludeDisabled
                    numberOfMonths={1}
                    className="w-full"
                />
            </div>
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

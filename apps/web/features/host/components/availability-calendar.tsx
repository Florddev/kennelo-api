"use client";

import { useCallback, useMemo } from "react";
import { Calendar, CalendarDayButton } from "@workspace/ui/components/calendar";
import { toApiDate } from "@workspace/common";
import type { AvailabilityModel } from "@workspace/modules/activities";
import type { PriceCalendar } from "@workspace/modules/activities";
import type { DateRange } from "react-day-picker";
import type { ComponentProps } from "react";

import { isDateDisabledForBooking } from "../lib/availability-helpers";

type AvailabilityCalendarProps = {
    dateRange: DateRange | undefined;
    onDateRangeChange: (range: DateRange | undefined) => void;
    availabilities: AvailabilityModel[];
    priceMap: PriceCalendar;
    numberOfMonths?: number;
};

export function AvailabilityCalendar({
    dateRange,
    onDateRangeChange,
    availabilities,
    priceMap,
    numberOfMonths = 1,
}: AvailabilityCalendarProps) {
    const today = useMemo(() => {
        const date = new Date();
        date.setHours(0, 0, 0, 0);
        return date;
    }, []);

    const disabledMatcher = useCallback(
        (date: Date) => isDateDisabledForBooking(date, availabilities, today),
        [availabilities, today],
    );

    const PricedDayButton = useCallback(
        (props: ComponentProps<typeof CalendarDayButton>) => {
            const price = props.modifiers.disabled
                ? null
                : (priceMap[toApiDate(props.day.date)] ?? null);
            return (
                <CalendarDayButton {...props}>
                    {props.children}
                    <div className="text-xs text-muted-foreground">
                        {price !== null && <span>{Math.round(price)}&nbsp;€</span>}
                    </div>
                </CalendarDayButton>
            );
        },
        [priceMap],
    );

    return (
        <Calendar
            mode="range"
            selected={dateRange}
            onSelect={onDateRangeChange}
            disabled={disabledMatcher}
            excludeDisabled
            numberOfMonths={numberOfMonths}
            className="w-full p-0 bg-card"
            components={{ DayButton: PricedDayButton }}
        />
    );
}

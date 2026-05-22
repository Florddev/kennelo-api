"use client";

import type { DateRange } from "react-day-picker";

import { Calendar } from "@workspace/ui/components/calendar";
import { cn } from "@workspace/ui/lib/utils";

type DatesPanelProps = {
    dateRange: DateRange | undefined;
    onSelect: (range: DateRange | undefined) => void;
    numberOfMonths?: number;
    className?: string;
    calendarClassName?: string;
};

export function DatesPanel({
    dateRange,
    onSelect,
    numberOfMonths = 2,
    className,
    calendarClassName,
}: DatesPanelProps) {
    return (
        <div
            data-slot="search-bar-dates-panel"
            className={cn(
                "absolute z-50 top-full mt-3 start-1/2 -translate-x-1/2 rtl:translate-x-1/2 bg-card rounded-2xl shadow-2xl ring-1 ring-border p-4 w-full",
                className,
            )}
        >
            <Calendar
                mode="range"
                selected={dateRange}
                onSelect={onSelect}
                numberOfMonths={numberOfMonths}
                disabled={{ before: new Date() }}
                className={cn("w-full bg-card", calendarClassName)}
            />
        </div>
    );
}

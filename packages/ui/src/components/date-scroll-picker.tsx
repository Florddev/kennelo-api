"use client";

import * as React from "react";
import { useCallback, useMemo, useState } from "react";

import {
    ITEM_SIZES,
    ScrollPicker,
    type ScrollPickerSize,
} from "@workspace/ui/components/scroll-picker";
import { cn } from "@workspace/ui/lib/utils";

export type DateScrollPickerLabels = {
    day?: string;
    month?: string;
    year?: string;
};

export type DateScrollPickerColumnWidths = {
    day?: number;
    month?: number;
    year?: number;
};

export type DateScrollPickerProps = {
    value: string;
    onChange: (value: string) => void;
    minYear?: number;
    maxYear?: number;
    monthFormat?: "numeric" | "short" | "long";
    locale?: string;
    labels?: DateScrollPickerLabels;
    orientation?: "horizontal" | "vertical";
    size?: ScrollPickerSize;
    itemSize?: number;
    columnWidths?: DateScrollPickerColumnWidths;
    sideItems?: number;
    className?: string;
};

function getDaysInMonth(year: number, month: number): number {
    return new Date(year, month, 0).getDate();
}

function parseIsoDate(iso: string): { day: number; month: number; year: number } {
    const [y, m, d] = iso.split("-").map(Number);
    const now = new Date();
    return {
        year: y || now.getFullYear(),
        month: m || now.getMonth() + 1,
        day: d || now.getDate(),
    };
}

function toIsoDate(year: number, month: number, day: number): string {
    return [
        String(year).padStart(4, "0"),
        String(month).padStart(2, "0"),
        String(day).padStart(2, "0"),
    ].join("-");
}

function DateScrollPicker({
    value,
    onChange,
    minYear,
    maxYear,
    monthFormat = "short",
    locale,
    labels,
    orientation = "horizontal",
    size = "sm",
    itemSize,
    columnWidths,
    sideItems = 2,
    className,
}: DateScrollPickerProps) {
    const now = new Date();
    const resolvedMinYear = minYear ?? now.getFullYear() - 100;
    const resolvedMaxYear = maxYear ?? now.getFullYear();

    const parsed = parseIsoDate(value);
    const [day, setDay] = useState(parsed.day);
    const [month, setMonth] = useState(parsed.month);
    const [year, setYear] = useState(
        Math.max(resolvedMinYear, Math.min(resolvedMaxYear, parsed.year)),
    );

    const maxDay = getDaysInMonth(year, month);

    const dayItems = useMemo(
        () =>
            Array.from({ length: maxDay }, (_, i) => ({
                label: String(i + 1),
                value: i + 1,
            })),
        [maxDay],
    );

    const monthItems = useMemo(() => {
        if (monthFormat === "numeric") {
            return Array.from({ length: 12 }, (_, i) => ({
                label: String(i + 1),
                value: i + 1,
            }));
        }
        return Array.from({ length: 12 }, (_, i) => ({
            label: new Intl.DateTimeFormat(locale, { month: monthFormat }).format(
                new Date(2000, i, 1),
            ),
            value: i + 1,
        }));
    }, [monthFormat, locale]);

    const yearItems = useMemo(
        () =>
            Array.from({ length: resolvedMaxYear - resolvedMinYear + 1 }, (_, i) => ({
                label: String(resolvedMinYear + i),
                value: resolvedMinYear + i,
            })),
        [resolvedMinYear, resolvedMaxYear],
    );

    const emit = useCallback(
        (nextDay: number, nextMonth: number, nextYear: number) => {
            const clampedDay = Math.min(nextDay, getDaysInMonth(nextYear, nextMonth));
            onChange(toIsoDate(nextYear, nextMonth, clampedDay));
        },
        [onChange],
    );

    const handleDayChange = useCallback(
        (v: string | number) => {
            const d = v as number;
            setDay(d);
            emit(d, month, year);
        },
        [month, year, emit],
    );

    const handleMonthChange = useCallback(
        (v: string | number) => {
            const m = v as number;
            setMonth(m);
            const clamped = Math.min(day, getDaysInMonth(year, m));
            setDay(clamped);
            emit(clamped, m, year);
        },
        [day, year, emit],
    );

    const handleYearChange = useCallback(
        (v: string | number) => {
            const y = v as number;
            setYear(y);
            const clamped = Math.min(day, getDaysInMonth(y, month));
            setDay(clamped);
            emit(clamped, month, y);
        },
        [day, month, emit],
    );

    const clampedDay = Math.min(day, maxDay);

    const resolvedItemH = itemSize ?? ITEM_SIZES[size];
    const defaultMonthWidth = monthFormat === "long" ? Math.round(resolvedItemH * 1.75) : undefined;

    const pickerProps = { orientation, size, itemSize, sideItems };

    return (
        <div data-slot="date-scroll-picker" className={cn("flex items-end gap-2", className)}>
            <Column label={labels?.day}>
                <ScrollPicker
                    items={dayItems}
                    value={clampedDay}
                    onChange={handleDayChange}
                    itemWidth={columnWidths?.day}
                    {...pickerProps}
                />
            </Column>

            <Column label={labels?.month}>
                <ScrollPicker
                    items={monthItems}
                    value={month}
                    onChange={handleMonthChange}
                    itemWidth={columnWidths?.month ?? defaultMonthWidth}
                    {...pickerProps}
                />
            </Column>

            <Column label={labels?.year}>
                <ScrollPicker
                    items={yearItems}
                    value={year}
                    onChange={handleYearChange}
                    itemWidth={columnWidths?.year}
                    {...pickerProps}
                />
            </Column>
        </div>
    );
}

function Column({ label, children }: { label?: string; children: React.ReactNode }) {
    return (
        <div className="flex flex-col items-center gap-1">
            {label && (
                <span className="text-xs font-medium text-muted-foreground tracking-wide">
                    {label}
                </span>
            )}
            {children}
        </div>
    );
}

export { DateScrollPicker };

"use client";

import { useMemo } from "react";
import { useLocale, useTranslations } from "next-intl";
import { ChevronLeftIcon, ChevronRightIcon } from "lucide-react";
import { addDays, format, isSameDay, isSameMonth } from "date-fns";

import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";
import type { BookingModel } from "@workspace/modules/bookings";

import { MAX_VISIBLE_ROWS, buildCalendarWeeks, dayKey, shiftMonth } from "../lib/calendar-grid";
import { activityColor } from "../lib/booking-colors";

type ActivityMeta = {
    id: string;
    name: string;
    colorIndex: number;
};

type BookingsCalendarProps = {
    focusedMonth: Date;
    onFocusedMonthChange: (date: Date) => void;
    bookings: BookingModel[];
    activityMetaById: Record<string, ActivityMeta>;
    onDayClick: (date: Date) => void;
};

const BAR_ROW_HEIGHT = 22;
const DATE_HEADER_HEIGHT = 36;
const OVERFLOW_HEIGHT = 18;

export function BookingsCalendar({
    focusedMonth,
    onFocusedMonthChange,
    bookings,
    activityMetaById,
    onDayClick,
}: BookingsCalendarProps) {
    const locale = useLocale();
    const t = useTranslations();

    const weekStartsOn: 0 | 1 = locale === "en" ? 0 : 1;

    const weeks = useMemo(
        () => buildCalendarWeeks(focusedMonth, bookings, weekStartsOn),
        [focusedMonth, bookings, weekStartsOn],
    );

    const weekdayLabels = useMemo(() => {
        const orderKeys =
            weekStartsOn === 1
                ? ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]
                : ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
        return orderKeys.map((key) =>
            t(`features.hosting-calendar.weekdays.${key}` as Parameters<typeof t>[0]),
        );
    }, [t, weekStartsOn]);

    const monthLabel = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, { month: "long", year: "numeric" }).format(
                focusedMonth,
            ),
        [focusedMonth, locale],
    );

    const today = new Date();
    const gridTemplateRows = `${DATE_HEADER_HEIGHT}px repeat(${MAX_VISIBLE_ROWS}, ${BAR_ROW_HEIGHT}px) 1fr ${OVERFLOW_HEIGHT}px`;

    return (
        <div className="flex flex-col" data-slot="bookings-calendar">
            <div className="flex items-center justify-between pb-4">
                <h2 className="text-xl font-semibold capitalize">{monthLabel}</h2>
                <div className="flex items-center gap-1">
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        onClick={() => onFocusedMonthChange(shiftMonth(focusedMonth, -1))}
                        aria-label="previous-month"
                    >
                        <ChevronLeftIcon className="size-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => onFocusedMonthChange(new Date())}
                    >
                        {format(new Date(), "MMM d")}
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        onClick={() => onFocusedMonthChange(shiftMonth(focusedMonth, 1))}
                        aria-label="next-month"
                    >
                        <ChevronRightIcon className="size-4" />
                    </Button>
                </div>
            </div>

            <div className="grid grid-cols-7 border-t border-s border-border rounded-t-2xl overflow-hidden">
                {weekdayLabels.map((label) => (
                    <div
                        key={label}
                        className="px-2 py-2 text-xs font-medium text-muted-foreground border-e border-b border-border bg-muted/30 text-start"
                    >
                        {label}
                    </div>
                ))}
            </div>

            <div className="flex flex-col border-s border-border rounded-b-2xl overflow-hidden">
                {weeks.map((week) => (
                    <div
                        key={week.weekStart.toISOString()}
                        className="grid grid-cols-7 min-h-32"
                        style={{ gridTemplateRows }}
                    >
                        {week.days.map((day, idx) => {
                            const isOutside = !isSameMonth(day, focusedMonth);
                            const isToday = isSameDay(day, today);

                            return (
                                <button
                                    type="button"
                                    key={dayKey(day)}
                                    onClick={() => onDayClick(day)}
                                    style={{
                                        gridColumn: `${idx + 1} / span 1`,
                                        gridRow: `1 / -1`,
                                    }}
                                    className={cn(
                                        "border-e border-b border-border text-start hover:bg-muted/40 focus:outline-none focus-visible:bg-muted/50 transition-colors p-2 flex flex-col",
                                        isOutside && "bg-muted/10 text-muted-foreground",
                                    )}
                                >
                                    <span
                                        className={cn(
                                            "text-sm font-medium inline-flex items-center justify-center size-6 rounded-full self-start",
                                            isToday &&
                                                "bg-primary text-primary-foreground font-semibold",
                                        )}
                                    >
                                        {day.getDate()}
                                    </span>
                                </button>
                            );
                        })}

                        {week.segments
                            .filter((segment) => !segment.hidden)
                            .map((segment) => {
                                const meta = activityMetaById[segment.booking.activityId];
                                const color = activityColor(meta?.colorIndex ?? 0);
                                const customerName =
                                    segment.booking.user?.firstName ??
                                    segment.booking.user?.email ??
                                    "—";

                                return (
                                    <button
                                        type="button"
                                        key={`${segment.booking.id}-${segment.weekStartIso}`}
                                        onClick={(event) => {
                                            event.stopPropagation();
                                            onDayClick(
                                                addDays(
                                                    new Date(segment.weekStartIso),
                                                    segment.colStart,
                                                ),
                                            );
                                        }}
                                        style={{
                                            gridColumn: `${segment.colStart + 1} / span ${segment.colSpan}`,
                                            gridRow: `${segment.row + 2}`,
                                        }}
                                        className={cn(
                                            "h-[20px] mx-1 px-2 text-[11px] font-medium truncate transition-colors flex items-center gap-1.5 z-10",
                                            color.barBg,
                                            color.barHoverBg,
                                            color.barText,
                                            segment.isStart
                                                ? "rounded-s-full"
                                                : "ms-0 rounded-s-none",
                                            segment.isEnd
                                                ? "rounded-e-full"
                                                : "me-0 rounded-e-none",
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                "size-1.5 rounded-full shrink-0",
                                                color.dot,
                                            )}
                                        />
                                        <span className="truncate">{customerName}</span>
                                    </button>
                                );
                            })}

                        {week.days.map((day, idx) => {
                            const key = dayKey(day);
                            const overflowCount = week.overflowByDayKey[key] ?? 0;
                            if (overflowCount === 0) return null;
                            return (
                                <span
                                    key={`overflow-${key}`}
                                    style={{
                                        gridColumn: `${idx + 1} / span 1`,
                                        gridRow: `${MAX_VISIBLE_ROWS + 3}`,
                                    }}
                                    className="text-[11px] text-muted-foreground px-2 pb-1 pointer-events-none z-10 truncate"
                                >
                                    {t("features.hosting-calendar.moreCount", {
                                        count: overflowCount,
                                    })}
                                </span>
                            );
                        })}
                    </div>
                ))}
            </div>
        </div>
    );
}

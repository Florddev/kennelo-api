import {
    differenceInCalendarDays,
    eachDayOfInterval,
    endOfMonth,
    endOfWeek,
    isWithinInterval,
    startOfDay,
    startOfMonth,
    startOfWeek,
} from "date-fns";

import type { BookingModel } from "@workspace/modules/bookings";

export const MAX_VISIBLE_ROWS = 3;

export type CalendarWeek = {
    weekStart: Date;
    days: Date[];
    segments: BookingSegment[];
    overflowByDayKey: Record<string, number>;
};

export type BookingSegment = {
    booking: BookingModel;
    weekStartIso: string;
    colStart: number;
    colSpan: number;
    row: number;
    isStart: boolean;
    isEnd: boolean;
    hidden: boolean;
};

export function dayKey(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

export function getMonthRange(focusedMonth: Date, weekStartsOn: 0 | 1) {
    const monthStart = startOfMonth(focusedMonth);
    const monthEnd = endOfMonth(focusedMonth);
    const gridStart = startOfWeek(monthStart, { weekStartsOn });
    const gridEnd = endOfWeek(monthEnd, { weekStartsOn });
    return { monthStart, monthEnd, gridStart, gridEnd };
}

export function buildCalendarWeeks(
    focusedMonth: Date,
    bookings: BookingModel[],
    weekStartsOn: 0 | 1,
): CalendarWeek[] {
    const { gridStart, gridEnd } = getMonthRange(focusedMonth, weekStartsOn);
    const allDays = eachDayOfInterval({ start: gridStart, end: gridEnd });
    const weeks: CalendarWeek[] = [];

    for (let i = 0; i < allDays.length; i += 7) {
        const days = allDays.slice(i, i + 7);
        const weekStart = days[0]!;
        const weekEnd = days[6]!;

        const candidates = bookings
            .map((booking) => buildSegmentForWeek(booking, weekStart, weekEnd))
            .filter((segment): segment is BookingSegment => segment !== null);

        const placed = assignRows(candidates);

        const overflowByDayKey: Record<string, number> = {};
        for (const segment of placed) {
            if (!segment.hidden) continue;
            for (let col = segment.colStart; col < segment.colStart + segment.colSpan; col++) {
                const day = days[col]!;
                const key = dayKey(day);
                overflowByDayKey[key] = (overflowByDayKey[key] ?? 0) + 1;
            }
        }

        weeks.push({
            weekStart,
            days,
            segments: placed,
            overflowByDayKey,
        });
    }

    return weeks;
}

function buildSegmentForWeek(
    booking: BookingModel,
    weekStart: Date,
    weekEnd: Date,
): BookingSegment | null {
    const checkIn = startOfDay(new Date(booking.checkInDate));
    const checkOut = startOfDay(new Date(booking.checkOutDate));
    const weekStartDay = startOfDay(weekStart);
    const weekEndDay = startOfDay(weekEnd);
    const weekInterval = { start: weekStartDay, end: weekEndDay };

    const overlaps =
        isWithinInterval(checkIn, weekInterval) ||
        isWithinInterval(checkOut, weekInterval) ||
        (checkIn < weekStartDay && checkOut > weekEndDay);

    if (!overlaps) return null;

    const segmentStart = checkIn < weekStartDay ? weekStartDay : checkIn;
    const segmentEnd = checkOut > weekEndDay ? weekEndDay : checkOut;

    const colStart = differenceInCalendarDays(segmentStart, weekStartDay);
    const colSpan = differenceInCalendarDays(segmentEnd, segmentStart) + 1;

    return {
        booking,
        weekStartIso: weekStartDay.toISOString(),
        colStart,
        colSpan,
        row: -1,
        isStart: checkIn >= weekStartDay,
        isEnd: checkOut <= weekEndDay,
        hidden: false,
    };
}

function assignRows(segments: BookingSegment[]): BookingSegment[] {
    const sorted = [...segments].sort((a, b) => {
        if (a.colStart !== b.colStart) return a.colStart - b.colStart;
        return b.colSpan - a.colSpan;
    });

    const rowEnds: number[] = [];

    for (const segment of sorted) {
        let assigned = -1;
        for (let row = 0; row < rowEnds.length; row++) {
            if (rowEnds[row]! <= segment.colStart) {
                assigned = row;
                break;
            }
        }
        if (assigned === -1) {
            assigned = rowEnds.length;
            rowEnds.push(0);
        }
        rowEnds[assigned] = segment.colStart + segment.colSpan;
        segment.row = assigned;
        segment.hidden = assigned >= MAX_VISIBLE_ROWS;
    }

    return sorted;
}

export function bookingsForDay(date: Date, bookings: BookingModel[]): BookingModel[] {
    const target = startOfDay(date);
    return bookings.filter((booking) => {
        const checkIn = startOfDay(new Date(booking.checkInDate));
        const checkOut = startOfDay(new Date(booking.checkOutDate));
        return target >= checkIn && target <= checkOut;
    });
}

export function weekdayOrder(weekStartsOn: 0 | 1): number[] {
    if (weekStartsOn === 1) return [1, 2, 3, 4, 5, 6, 0];
    return [0, 1, 2, 3, 4, 5, 6];
}

export function shiftMonth(date: Date, delta: number): Date {
    const next = new Date(date);
    next.setDate(1);
    next.setMonth(next.getMonth() + delta);
    return next;
}

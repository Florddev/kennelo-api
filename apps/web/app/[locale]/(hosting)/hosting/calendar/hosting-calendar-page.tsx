"use client";

import { useMemo, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Building2 } from "lucide-react";

import { Separator } from "@workspace/ui/components/separator";
import type { BookingStatus } from "@workspace/modules/bookings";

import { useAuth } from "@/features/auth";
import { useCalendarActivityBookings } from "@/features/bookings/hooks/use-calendar-activity-bookings";
import {
    BookingsCalendar,
    CalendarSidebar,
} from "@/features/bookings/components/bookings-calendar";
import { DayBookingsSheet } from "@/features/bookings/components/day-bookings-sheet";
import {
    EMPTY_DAY_MOVEMENTS,
    buildMonthWeeks,
    buildMovementsByDay,
    computeMonthStats,
    movementsForDay,
} from "@/features/bookings/lib/calendar-grid";
import { DEFAULT_ACTIVE_STATUSES } from "@/features/bookings/lib/booking-colors";

export default function HostingCalendarPage() {
    const t = useTranslations();
    const locale = useLocale();
    const { activities, isLoaded } = useAuth();

    const activityMetaById = useMemo(() => {
        const map: Record<string, { id: string; name: string; colorIndex: number }> = {};
        activities.forEach((activity, index) => {
            map[activity.id] = {
                id: activity.id,
                name: activity.name,
                colorIndex: index,
            };
        });
        return map;
    }, [activities]);

    const [focusedMonth, setFocusedMonth] = useState(() => new Date());
    const [selectedActivityIds, setSelectedActivityIds] = useState<string[] | null>(null);
    const [activeStatuses, setActiveStatuses] = useState<BookingStatus[]>(DEFAULT_ACTIVE_STATUSES);
    const [selectedDate, setSelectedDate] = useState<Date | null>(null);
    const [sheetOpen, setSheetOpen] = useState(false);

    const allActivityIds = useMemo(() => activities.map((activity) => activity.id), [activities]);
    const activeActivityIds = selectedActivityIds ?? allActivityIds;

    const weekStartsOn: 0 | 1 = locale === "en" ? 0 : 1;

    const { bookingsByActivityId } = useCalendarActivityBookings({
        activityIds: allActivityIds,
        focusedMonth,
        weekStartsOn,
    });

    const filteredBookings = useMemo(() => {
        const activeActivities = new Set(activeActivityIds);
        const activeStatusSet = new Set(activeStatuses);
        return Object.entries(bookingsByActivityId)
            .filter(([activityId]) => activeActivities.has(activityId))
            .flatMap(([, bookings]) => bookings)
            .filter((booking) => activeStatusSet.has(booking.status));
    }, [bookingsByActivityId, activeActivityIds, activeStatuses]);

    const weeks = useMemo(
        () => buildMonthWeeks(focusedMonth, weekStartsOn),
        [focusedMonth, weekStartsOn],
    );

    const movementsByDay = useMemo(
        () => buildMovementsByDay(weeks, filteredBookings),
        [weeks, filteredBookings],
    );

    const stats = useMemo(
        () => computeMonthStats(weeks, movementsByDay, focusedMonth, filteredBookings),
        [weeks, movementsByDay, focusedMonth, filteredBookings],
    );

    const dayMovements = useMemo(
        () =>
            selectedDate ? movementsForDay(selectedDate, filteredBookings) : EMPTY_DAY_MOVEMENTS,
        [selectedDate, filteredBookings],
    );

    const toggleActivity = (id: string) => {
        setSelectedActivityIds((prev) => {
            const base = prev ?? allActivityIds;
            return base.includes(id) ? base.filter((eid) => eid !== id) : [...base, id];
        });
    };

    const toggleAllActivities = () => {
        setSelectedActivityIds((prev) => {
            const base = prev ?? allActivityIds;
            return base.length === activities.length
                ? []
                : activities.map((activity) => activity.id);
        });
    };

    const toggleStatus = (status: BookingStatus) => {
        setActiveStatuses((prev) =>
            prev.includes(status) ? prev.filter((s) => s !== status) : [...prev, status],
        );
    };

    const onDayClick = (date: Date) => {
        setSelectedDate(date);
        setSheetOpen(true);
    };

    if (!isLoaded) {
        return null;
    }

    if (activities.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center h-[calc(100dvh-var(--header-height))] gap-3 px-6 text-center">
                <div className="flex items-center justify-center size-16 rounded-full bg-muted">
                    <Building2 className="size-8 text-muted-foreground" />
                </div>
                <h2 className="text-lg font-semibold">
                    {t("features.hosting-calendar.noActivity.title")}
                </h2>
                <p className="text-sm text-muted-foreground max-w-sm">
                    {t("features.hosting-calendar.noActivity.description")}
                </p>
            </div>
        );
    }

    return (
        <>
            <div className="flex flex-col md:flex-row w-full md:h-[calc(100dvh-var(--header-height))]">
                <div className="w-full md:max-w-76 shrink-0 p-4 md:p-6 md:overflow-y-auto">
                    <CalendarSidebar
                        focusedMonth={focusedMonth}
                        stats={stats}
                        activities={activities}
                        activityMetaById={activityMetaById}
                        activeActivityIds={activeActivityIds}
                        onToggleActivity={toggleActivity}
                        onToggleAllActivities={toggleAllActivities}
                        activeStatuses={activeStatuses}
                        onToggleStatus={toggleStatus}
                    />
                </div>

                <Separator orientation="vertical" className="hidden md:block w-[1px] h-full" />

                <div className="flex-1 min-w-0 md:overflow-y-auto">
                    <BookingsCalendar
                        focusedMonth={focusedMonth}
                        onFocusedMonthChange={setFocusedMonth}
                        weeks={weeks}
                        movementsByDay={movementsByDay}
                        peak={stats.peak}
                        weekStartsOn={weekStartsOn}
                        onDayClick={onDayClick}
                    />
                </div>
            </div>

            <DayBookingsSheet
                open={sheetOpen}
                onOpenChange={setSheetOpen}
                selectedDate={selectedDate}
                movements={dayMovements}
            />
        </>
    );
}

"use client";

import { useMemo, useState } from "react";
import { useTranslations } from "next-intl";
import { Building2 } from "lucide-react";
import { Calendar } from "@solar-icons/react";

import { Badge } from "@workspace/ui/components/badge";
import { cn } from "@workspace/ui/lib/utils";
import type { BookingStatus } from "@workspace/modules/bookings";

import { useAuth } from "@/features/auth";
import { useCalendarActivityBookings } from "@/features/bookings/hooks/use-calendar-activity-bookings";
import { BookingsCalendar } from "@/features/bookings/components/bookings-calendar";
import { DayBookingsSheet } from "@/features/bookings/components/day-bookings-sheet";
import { bookingsForDay } from "@/features/bookings/lib/calendar-grid";
import {
    DEFAULT_ACTIVE_STATUSES,
    SELECTABLE_STATUSES,
    activityColor,
    statusColor,
} from "@/features/bookings/lib/booking-colors";
import PageLayout from "@/components/layouts/page-layout";

export default function HostingCalendarPage() {
    const t = useTranslations();
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
    const [activeActivityIds, setActiveActivityIds] = useState<string[]>(() =>
        activities.map((activity) => activity.id),
    );
    const [activeStatuses, setActiveStatuses] = useState<BookingStatus[]>(DEFAULT_ACTIVE_STATUSES);
    const [selectedDate, setSelectedDate] = useState<Date | null>(null);
    const [sheetOpen, setSheetOpen] = useState(false);

    const allActivityIds = useMemo(() => activities.map((activity) => activity.id), [activities]);

    const weekStartsOn: 0 | 1 = 1;

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

    const dayBookings = useMemo(
        () => (selectedDate ? bookingsForDay(selectedDate, filteredBookings) : []),
        [selectedDate, filteredBookings],
    );

    const toggleActivity = (id: string) => {
        setActiveActivityIds((prev) =>
            prev.includes(id) ? prev.filter((eid) => eid !== id) : [...prev, id],
        );
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

    const filters = (
        <div className="flex flex-col gap-3">
            {activities.length > 1 && (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-xs font-medium text-muted-foreground me-1">
                        {t("features.hosting-calendar.filters.activities")}
                    </span>
                    {activities.map((activity) => {
                        const meta = activityMetaById[activity.id]!;
                        const color = activityColor(meta.colorIndex);
                        const active = activeActivityIds.includes(activity.id);
                        return (
                            <button
                                key={activity.id}
                                type="button"
                                onClick={() => toggleActivity(activity.id)}
                                className={cn(
                                    "inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-opacity",
                                    color.chipBg,
                                    color.chipBorder,
                                    color.chipText,
                                    !active && "opacity-40",
                                )}
                            >
                                <span className={cn("size-2 rounded-full", color.dot)} />
                                {activity.name}
                            </button>
                        );
                    })}
                </div>
            )}

            <div className="flex flex-wrap items-center gap-2">
                <span className="text-xs font-medium text-muted-foreground me-1">
                    {t("features.hosting-calendar.filters.statuses")}
                </span>
                {SELECTABLE_STATUSES.map((status) => {
                    const color = statusColor(status);
                    const active = activeStatuses.includes(status);
                    return (
                        <button
                            key={status}
                            type="button"
                            onClick={() => toggleStatus(status)}
                            className={cn("transition-opacity", !active && "opacity-40")}
                        >
                            <Badge variant="outline" className={cn(color.badge)}>
                                {t(
                                    `features.hosting-calendar.status.${status}` as Parameters<
                                        typeof t
                                    >[0],
                                )}
                            </Badge>
                        </button>
                    );
                })}
            </div>
        </div>
    );

    return (
        <PageLayout
            Icon={Calendar}
            title={t("ui.navigation.hosting.calendar")}
            headerBottom={filters}
            className="px-6 pb-6 pt-2 space-y-0"
        >
            <div className="flex flex-col gap-4">
                <BookingsCalendar
                    focusedMonth={focusedMonth}
                    onFocusedMonthChange={setFocusedMonth}
                    bookings={filteredBookings}
                    activityMetaById={activityMetaById}
                    onDayClick={onDayClick}
                />
                <DayBookingsSheet
                    open={sheetOpen}
                    onOpenChange={setSheetOpen}
                    selectedDate={selectedDate}
                    bookings={dayBookings}
                    activityMetaById={activityMetaById}
                />
            </div>
        </PageLayout>
    );
}

"use client";

import { useMemo, useState } from "react";
import { useTranslations } from "next-intl";
import { Building2 } from "lucide-react";
import { Calendar } from "@solar-icons/react";

import { Badge } from "@workspace/ui/components/badge";
import { cn } from "@workspace/ui/lib/utils";
import type { BookingStatus } from "@workspace/modules/bookings";

import { useAuth } from "@/features/auth";
import { useEstablishmentBookings } from "@/features/bookings/hooks/use-establishment-bookings";
import { BookingsCalendar } from "@/features/bookings/components/bookings-calendar";
import { DayBookingsSheet } from "@/features/bookings/components/day-bookings-sheet";
import { bookingsForDay } from "@/features/bookings/lib/calendar-grid";
import {
    DEFAULT_ACTIVE_STATUSES,
    SELECTABLE_STATUSES,
    establishmentColor,
    statusColor,
} from "@/features/bookings/lib/booking-colors";
import PageLayout from "@/components/layouts/page-layout";

export default function HostingCalendarPage() {
    const t = useTranslations();
    const { establishments, isLoaded } = useAuth();

    const establishmentMetaById = useMemo(() => {
        const map: Record<string, { id: string; name: string; colorIndex: number }> = {};
        establishments.forEach((establishment, index) => {
            map[establishment.id] = {
                id: establishment.id,
                name: establishment.name,
                colorIndex: index,
            };
        });
        return map;
    }, [establishments]);

    const [focusedMonth, setFocusedMonth] = useState(() => new Date());
    const [activeEstablishmentIds, setActiveEstablishmentIds] = useState<string[]>(() =>
        establishments.map((establishment) => establishment.id),
    );
    const [activeStatuses, setActiveStatuses] = useState<BookingStatus[]>(DEFAULT_ACTIVE_STATUSES);
    const [selectedDate, setSelectedDate] = useState<Date | null>(null);
    const [sheetOpen, setSheetOpen] = useState(false);

    const allEstablishmentIds = useMemo(
        () => establishments.map((establishment) => establishment.id),
        [establishments],
    );

    const weekStartsOn: 0 | 1 = 1;

    const { bookingsByEstablishmentId } = useEstablishmentBookings({
        establishmentIds: allEstablishmentIds,
        focusedMonth,
        weekStartsOn,
    });

    const filteredBookings = useMemo(() => {
        const activeEstablishments = new Set(activeEstablishmentIds);
        const activeStatusSet = new Set(activeStatuses);
        return Object.entries(bookingsByEstablishmentId)
            .filter(([establishmentId]) => activeEstablishments.has(establishmentId))
            .flatMap(([, bookings]) => bookings)
            .filter((booking) => activeStatusSet.has(booking.status));
    }, [bookingsByEstablishmentId, activeEstablishmentIds, activeStatuses]);

    const dayBookings = useMemo(
        () => (selectedDate ? bookingsForDay(selectedDate, filteredBookings) : []),
        [selectedDate, filteredBookings],
    );

    const toggleEstablishment = (id: string) => {
        setActiveEstablishmentIds((prev) =>
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

    if (establishments.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center h-[calc(100dvh-var(--header-height))] gap-3 px-6 text-center">
                <div className="flex items-center justify-center size-16 rounded-full bg-muted">
                    <Building2 className="size-8 text-muted-foreground" />
                </div>
                <h2 className="text-lg font-semibold">
                    {t("features.hosting-calendar.noEstablishment.title")}
                </h2>
                <p className="text-sm text-muted-foreground max-w-sm">
                    {t("features.hosting-calendar.noEstablishment.description")}
                </p>
            </div>
        );
    }

    const filters = (
        <div className="flex flex-col gap-3">
            {establishments.length > 1 && (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-xs font-medium text-muted-foreground me-1">
                        {t("features.hosting-calendar.filters.establishments")}
                    </span>
                    {establishments.map((establishment) => {
                        const meta = establishmentMetaById[establishment.id]!;
                        const color = establishmentColor(meta.colorIndex);
                        const active = activeEstablishmentIds.includes(establishment.id);
                        return (
                            <button
                                key={establishment.id}
                                type="button"
                                onClick={() => toggleEstablishment(establishment.id)}
                                className={cn(
                                    "inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-opacity",
                                    color.chipBg,
                                    color.chipBorder,
                                    color.chipText,
                                    !active && "opacity-40",
                                )}
                            >
                                <span className={cn("size-2 rounded-full", color.dot)} />
                                {establishment.name}
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
                    establishmentMetaById={establishmentMetaById}
                    onDayClick={onDayClick}
                />
                <DayBookingsSheet
                    open={sheetOpen}
                    onOpenChange={setSheetOpen}
                    selectedDate={selectedDate}
                    bookings={dayBookings}
                    establishmentMetaById={establishmentMetaById}
                />
            </div>
        </PageLayout>
    );
}

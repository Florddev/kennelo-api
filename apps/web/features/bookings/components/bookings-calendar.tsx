"use client";

import { useMemo } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Calendar } from "@solar-icons/react";
import {
    CalendarRange,
    Check,
    ChevronLeftIcon,
    ChevronRightIcon,
    LogIn,
    LogOut,
    PawPrint,
} from "lucide-react";
import { format, isSameDay, isSameMonth } from "date-fns";

import { Badge } from "@workspace/ui/components/badge";
import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";
import type { BookingStatus } from "@workspace/modules/bookings";

import PageLayout from "@/components/layouts/page-layout";
import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";

import { activityColor, statusColor, SELECTABLE_STATUSES } from "../lib/booking-colors";
import {
    EMPTY_DAY_MOVEMENTS,
    dayKey,
    shiftMonth,
    type DayMovements,
    type MonthStats,
    type PetMovement,
} from "../lib/calendar-grid";

type ActivityMeta = {
    id: string;
    name: string;
    colorIndex: number;
};

const MAX_VISIBLE_MOVEMENTS = 2;

type MovementKind = "arrival" | "departure";

const MOVEMENT_STYLES: Record<MovementKind, { pill: string; Icon: typeof LogIn }> = {
    arrival: {
        pill: "bg-emerald-500/10 border-emerald-500/25 text-emerald-700 dark:text-emerald-300",
        Icon: LogIn,
    },
    departure: {
        pill: "bg-rose-500/10 border-rose-500/25 text-rose-700 dark:text-rose-300",
        Icon: LogOut,
    },
};

type BookingsCalendarProps = {
    focusedMonth: Date;
    onFocusedMonthChange: (date: Date) => void;
    weeks: Date[][];
    movementsByDay: Record<string, DayMovements>;
    peak: number;
    weekStartsOn: 0 | 1;
    onDayClick: (date: Date) => void;
};

export function BookingsCalendar({
    focusedMonth,
    onFocusedMonthChange,
    weeks,
    movementsByDay,
    peak,
    weekStartsOn,
    onDayClick,
}: BookingsCalendarProps) {
    const locale = useLocale();
    const t = useTranslations();

    const weekdayLabels = useMemo(() => {
        const orderKeys =
            weekStartsOn === 1
                ? ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]
                : ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
        return orderKeys.map((key) => ({
            label: t(`features.hosting-calendar.weekdays.${key}` as Parameters<typeof t>[0]),
            weekend: key === "saturday" || key === "sunday",
        }));
    }, [t, weekStartsOn]);

    const monthLabel = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, { month: "long", year: "numeric" }).format(
                focusedMonth,
            ),
        [focusedMonth, locale],
    );

    const today = new Date();

    return (
        <div className="flex flex-col" data-slot="bookings-calendar">
            <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-2">
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

            <div className="border-t overflow-hidden">
                <div className="grid grid-cols-7">
                    {weekdayLabels.map(({ label, weekend }) => (
                        <div
                            key={label}
                            className={cn(
                                "px-2 py-2 text-xs font-medium text-muted-foreground border-e border-b border-border last:border-e-0 text-start",
                                weekend ? "bg-muted/40" : "bg-muted/20",
                            )}
                        >
                            {label}
                        </div>
                    ))}
                </div>

                <div className="flex flex-col">
                    {weeks.map((week, weekIndex) => (
                        <div key={dayKey(week[0]!)} className="grid grid-cols-7">
                            {week.map((day) => {
                                const isLastWeek = weekIndex === weeks.length - 1;
                                const key = dayKey(day);
                                const isOutside = !isSameMonth(day, focusedMonth);
                                const isToday = isSameDay(day, today);
                                const isWeekend = day.getDay() === 0 || day.getDay() === 6;
                                const movements = movementsByDay[key] ?? EMPTY_DAY_MOVEMENTS;

                                const cellMovements = [
                                    ...movements.arrivals.map((movement) => ({
                                        movement,
                                        kind: "arrival" as const,
                                    })),
                                    ...movements.departures.map((movement) => ({
                                        movement,
                                        kind: "departure" as const,
                                    })),
                                ];
                                const visible = cellMovements.slice(0, MAX_VISIBLE_MOVEMENTS);
                                const overflow = cellMovements.length - visible.length;
                                const fillRatio = peak > 0 ? movements.occupancy / peak : 0;

                                return (
                                    <button
                                        type="button"
                                        key={key}
                                        onClick={() => onDayClick(day)}
                                        className={cn(
                                            "relative border-e border-b border-border last:border-e-0 text-start hover:bg-muted/40 focus:outline-none focus-visible:bg-muted/50 transition-colors p-1.5 flex flex-col gap-1 min-h-28 md:min-h-36 min-w-0",
                                            isLastWeek && "border-b-0",
                                            isOutside
                                                ? "bg-muted/10 text-muted-foreground"
                                                : isWeekend && "bg-muted/20",
                                            isToday && "ring-2 ring-inset ring-primary z-10",
                                        )}
                                    >
                                        <div className="flex items-start justify-between gap-1">
                                            <span
                                                className={cn(
                                                    "text-sm font-medium inline-flex items-center justify-center size-6 rounded-full",
                                                    isToday &&
                                                        "bg-primary text-primary-foreground font-semibold",
                                                )}
                                            >
                                                {day.getDate()}
                                            </span>
                                        </div>

                                        <div className="flex flex-col gap-0.5 min-w-0">
                                            {visible.map(({ movement, kind }) => (
                                                <MovementRow
                                                    key={`${kind}-${movement.bookingId}-${movement.petId}`}
                                                    movement={movement}
                                                    kind={kind}
                                                />
                                            ))}
                                            {overflow > 0 && (
                                                <span className="text-[11px] text-muted-foreground ps-1">
                                                    {t("features.hosting-calendar.moreCount", {
                                                        count: overflow,
                                                    })}
                                                </span>
                                            )}
                                        </div>

                                        {movements.occupancy > 0 && (
                                            <div className="flex flex-col items-end gap-1 mt-auto">
                                                <span className="inline-flex items-center gap-1 rounded-full text-[10px] font-semibold text-muted-foreground">
                                                    {/* <PawPrint className="size-3 shrink-0" /> */}
                                                    {movements.occupancy}/{peak}
                                                </span>

                                                <div className="w-full mt-auto h-1 rounded-full bg-border/60 overflow-hidden">
                                                    <div
                                                        className="h-full rounded-full bg-secondary"
                                                        style={{ width: `${fillRatio * 100}%` }}
                                                    />
                                                </div>
                                            </div>
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

type CalendarSidebarProps = {
    focusedMonth: Date;
    stats: MonthStats;
    activities: { id: string; name: string; type?: string | null }[];
    activityMetaById: Record<string, ActivityMeta>;
    activeActivityIds: string[];
    onToggleActivity: (id: string) => void;
    onToggleAllActivities: () => void;
    activeStatuses: BookingStatus[];
    onToggleStatus: (status: BookingStatus) => void;
};

export function CalendarSidebar({
    focusedMonth,
    stats,
    activities,
    activityMetaById,
    activeActivityIds,
    onToggleActivity,
    onToggleAllActivities,
    activeStatuses,
    onToggleStatus,
}: CalendarSidebarProps) {
    const locale = useLocale();
    const t = useTranslations();

    const monthLabel = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, { month: "long", year: "numeric" }).format(
                focusedMonth,
            ),
        [focusedMonth, locale],
    );

    const allActive =
        activities.length > 0 &&
        activities.every((activity) => activeActivityIds.includes(activity.id));

    return (
        <PageLayout
            title={t("ui.navigation.hosting.calendar")}
            Icon={Calendar}
            className="space-y-6"
        >
            <section className="flex flex-col gap-2.5 hidden" data-slot="calendar-summary">
                <div className="flex items-baseline justify-between">
                    <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        {t("features.hosting-calendar.sidebar.summary")}
                    </h3>
                    <span className="text-xs text-muted-foreground capitalize">{monthLabel}</span>
                </div>
                <div className="grid grid-cols-2 gap-2">
                    <SummaryTile
                        Icon={LogIn}
                        value={stats.arrivals}
                        label={t("features.hosting-calendar.summary.arrivals")}
                        tone="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                    />
                    <SummaryTile
                        Icon={LogOut}
                        value={stats.departures}
                        label={t("features.hosting-calendar.summary.departures")}
                        tone="bg-rose-500/10 text-rose-600 dark:text-rose-400"
                    />
                    <SummaryTile
                        Icon={PawPrint}
                        value={stats.peak}
                        label={t("features.hosting-calendar.summary.peak")}
                        tone="bg-primary/10 text-primary"
                    />
                    <SummaryTile
                        Icon={CalendarRange}
                        value={stats.stays}
                        label={t("features.hosting-calendar.summary.stays")}
                        tone="bg-sky-500/10 text-sky-600 dark:text-sky-400"
                    />
                </div>
            </section>

            <section className="flex flex-col gap-2.5" data-slot="calendar-activities">
                <div className="flex items-center justify-between">
                    <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        {t("features.hosting-calendar.filters.activities")}
                    </h3>
                    {activities.length > 1 && (
                        <button
                            type="button"
                            onClick={onToggleAllActivities}
                            className="text-xs font-medium text-primary hover:underline"
                        >
                            {allActive
                                ? t("features.hosting-calendar.filters.none")
                                : t("features.hosting-calendar.filters.all")}
                        </button>
                    )}
                </div>
                <div className="flex flex-col gap-1.5">
                    {activities.map((activity) => {
                        const meta = activityMetaById[activity.id];
                        const color = activityColor(meta?.colorIndex ?? 0);
                        const active = activeActivityIds.includes(activity.id);
                        return (
                            <button
                                key={activity.id}
                                type="button"
                                onClick={() => onToggleActivity(activity.id)}
                                aria-pressed={active}
                                className={cn(
                                    "flex items-center gap-2.5 rounded-2xl border px-3 py-2.5 text-sm text-start transition-colors",
                                    active
                                        ? "border-border bg-card"
                                        : "border-transparent bg-muted/30 opacity-60",
                                )}
                            >
                                <span className={cn("size-2.5 rounded-full shrink-0", color.dot)} />
                                <span className="flex flex-1 flex-col min-w-0">
                                    <span className="truncate font-medium leading-tight">
                                        {activity.name}
                                    </span>
                                    {activity.type && (
                                        <span className="truncate text-xs text-muted-foreground">
                                            {t(
                                                `features.activities.types.${activity.type}` as Parameters<
                                                    typeof t
                                                >[0],
                                            )}
                                        </span>
                                    )}
                                </span>
                                <span
                                    className={cn(
                                        "flex items-center justify-center size-5 rounded-full border shrink-0",
                                        active
                                            ? "border-primary bg-primary text-primary-foreground"
                                            : "border-muted-foreground/40",
                                    )}
                                >
                                    {active && <Check className="size-3.5" />}
                                </span>
                            </button>
                        );
                    })}
                </div>
            </section>

            <section className="flex flex-col gap-2.5" data-slot="calendar-legend">
                <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    {t("features.hosting-calendar.sidebar.legend")}
                </h3>
                <div className="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-muted-foreground">
                    <LegendDot className="bg-emerald-500">
                        {t("features.hosting-calendar.legend.arrival")}
                    </LegendDot>
                    <LegendDot className="bg-rose-500">
                        {t("features.hosting-calendar.legend.departure")}
                    </LegendDot>
                    <span className="inline-flex items-center gap-1.5">
                        <span className="h-1.5 w-6 rounded-full bg-primary/70" />
                        {t("features.hosting-calendar.legend.occupancy")}
                    </span>
                </div>
            </section>

            <section className="flex flex-col gap-2.5" data-slot="calendar-statuses">
                <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    {t("features.hosting-calendar.filters.statuses")}
                </h3>
                <div className="flex flex-wrap gap-2">
                    {SELECTABLE_STATUSES.map((status) => {
                        const color = statusColor(status);
                        const active = activeStatuses.includes(status);
                        return (
                            <button
                                key={status}
                                type="button"
                                onClick={() => onToggleStatus(status)}
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
            </section>
        </PageLayout>
    );
}

function SummaryTile({
    Icon,
    value,
    label,
    tone,
}: {
    Icon: typeof LogIn;
    value: number;
    label: string;
    tone: string;
}) {
    return (
        <div
            data-slot="calendar-summary-tile"
            className="flex items-center gap-3 rounded-2xl border bg-card p-3"
        >
            <span
                className={cn(
                    "flex items-center justify-center size-9 rounded-full shrink-0",
                    tone,
                )}
            >
                <Icon className="size-5" />
            </span>
            <div className="flex flex-col min-w-0">
                <span className="text-lg font-semibold leading-none">{value}</span>
                <span className="text-xs text-muted-foreground truncate">{label}</span>
            </div>
        </div>
    );
}

function LegendDot({ className, children }: { className: string; children: React.ReactNode }) {
    return (
        <span className="inline-flex items-center gap-1.5">
            <span className={cn("size-2 rounded-full", className)} />
            {children}
        </span>
    );
}

function MovementRow({ movement, kind }: { movement: PetMovement; kind: MovementKind }) {
    const style = MOVEMENT_STYLES[kind];
    const Icon = style.Icon;
    const illustrated =
        movement.animalTypeCode !== null && isIllustratedType(movement.animalTypeCode);

    return (
        <span
            className={cn(
                "flex items-center gap-1 rounded-full border py-[3px] ps-[3px] pe-1.5 min-w-0",
                style.pill,
            )}
        >
            <span className="flex items-center justify-center size-4 rounded-full bg-background shrink-0">
                {illustrated ? (
                    <PetTypeIllustration
                        code={movement.animalTypeCode!}
                        name={movement.animalTypeName ?? ""}
                        className="size-3"
                    />
                ) : (
                    <PawPrint className="size-2.5 opacity-70" />
                )}
            </span>
            <span className="flex-1 truncate text-[11px] font-medium leading-none">
                {movement.petName}
            </span>
            <Icon className="size-2.5 shrink-0 opacity-70" />
        </span>
    );
}

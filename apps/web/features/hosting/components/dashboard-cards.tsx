"use client";

import Link from "next/link";
import { useTranslations } from "next-intl";
import { LogIn, LogOut, PawPrint, TrendingDown, TrendingUp } from "lucide-react";

import { formatAmount } from "@workspace/common";
import { cn } from "@workspace/ui/lib/utils";

import { useNavigation } from "@/hooks/use-navigation";
import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import { isIllustratedType } from "@/features/pets/lib/pet-illustrations";
import type { HostingTodaySummary } from "@/features/hosting/hooks/use-hosting-today";
import type { PetMovement } from "@/features/bookings/lib/calendar-grid";

type Routes = ReturnType<typeof useNavigation>["routes"];

export function StatCard({
    Icon,
    label,
    value,
    subtitle,
    tone,
    href,
}: {
    Icon: typeof PawPrint;
    label: string;
    value: string | number;
    subtitle?: string;
    tone: string;
    href?: string;
}) {
    const content = (
        <div
            data-slot="dashboard-stat-card"
            className={cn(
                "flex h-full flex-col gap-3 rounded-2xl border bg-card p-4",
                href && "transition-colors hover:bg-muted/40",
            )}
        >
            <div className="flex items-center justify-between gap-2">
                <span className="text-xs font-medium text-muted-foreground">{label}</span>
                <span className={cn("flex items-center justify-center size-8 rounded-full", tone)}>
                    <Icon className="size-4" />
                </span>
            </div>
            <div className="flex flex-col gap-0.5">
                <span className="text-2xl font-bold leading-none tabular-nums">{value}</span>
                {subtitle && (
                    <span className="text-xs text-muted-foreground truncate">{subtitle}</span>
                )}
            </div>
        </div>
    );

    return href ? <Link href={href}>{content}</Link> : content;
}

export function RevenueCard({
    revenue,
    locale,
}: {
    revenue: HostingTodaySummary["revenue"];
    locale: string;
}) {
    const t = useTranslations();
    const max = Math.max(...revenue.series.map((point) => point.amount), 1);
    const lastIndex = revenue.series.length - 1;

    return (
        <div
            data-slot="dashboard-revenue-card"
            className="flex flex-col gap-5 rounded-2xl border bg-card p-5"
        >
            <div className="flex items-start justify-between gap-3">
                <div className="flex flex-col gap-1">
                    <span className="text-sm text-muted-foreground">
                        {t("features.hosting-today.revenue.evolution")}
                    </span>
                    <div className="flex items-end gap-2">
                        <span className="text-3xl font-bold leading-none">
                            {formatAmount(revenue.currentMonth)} €
                        </span>
                        <RevenueDelta changeRate={revenue.changeRate} />
                    </div>
                    <span className="text-xs text-muted-foreground">
                        {t("features.hosting-today.revenue.thisMonth")}
                    </span>
                </div>
                <span className="flex items-center justify-center size-10 rounded-full bg-primary/10 text-primary shrink-0">
                    <TrendingUp className="size-5" />
                </span>
            </div>

            <div className="flex flex-col gap-2">
                <div className="flex items-end gap-1.5 h-28">
                    {revenue.series.map((point, index) => {
                        const height = max > 0 ? Math.max((point.amount / max) * 100, 3) : 3;
                        return (
                            <div
                                key={point.month}
                                className="flex h-full flex-1 items-end min-w-0"
                                title={`${formatAmount(point.amount)} €`}
                            >
                                <div
                                    className={cn(
                                        "w-full rounded-md transition-all",
                                        index === lastIndex ? "bg-primary" : "bg-primary/25",
                                    )}
                                    style={{ height: `${height}%` }}
                                />
                            </div>
                        );
                    })}
                </div>
                <div className="flex gap-1.5">
                    {revenue.series.map((point, index) => (
                        <span
                            key={point.month}
                            className={cn(
                                "flex-1 text-center text-[10px] capitalize",
                                index === lastIndex
                                    ? "font-medium text-foreground"
                                    : "text-muted-foreground",
                            )}
                        >
                            {monthShort(point.month, locale)}
                        </span>
                    ))}
                </div>
            </div>
        </div>
    );
}

function RevenueDelta({ changeRate }: { changeRate: number | null }) {
    const t = useTranslations();

    if (changeRate === null) {
        return (
            <span className="text-xs font-medium text-muted-foreground pb-0.5">
                {t("features.hosting-today.revenue.newBadge")}
            </span>
        );
    }

    const positive = changeRate >= 0;
    const Icon = positive ? TrendingUp : TrendingDown;

    return (
        <span
            title={t("features.hosting-today.revenue.vsPrevious")}
            className={cn(
                "inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold",
                positive
                    ? "bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                    : "bg-rose-500/10 text-rose-600 dark:text-rose-400",
            )}
        >
            <Icon className="size-3" />
            {positive ? "+" : ""}
            {changeRate}%
        </span>
    );
}

const MOVEMENT_CONFIG = {
    arrival: {
        Icon: LogIn,
        iconClass: "text-emerald-600 dark:text-emerald-400",
        chipClass: "bg-emerald-500/10",
        titleKey: "features.hosting-today.sections.arrivals",
        emptyKey: "features.hosting-today.sections.noArrivals",
    },
    departure: {
        Icon: LogOut,
        iconClass: "text-rose-600 dark:text-rose-400",
        chipClass: "bg-rose-500/10",
        titleKey: "features.hosting-today.sections.departures",
        emptyKey: "features.hosting-today.sections.noDepartures",
    },
} as const;

export function MovementCard({
    kind,
    items,
    routes,
    activityNameById,
    showActivity,
}: {
    kind: "arrival" | "departure";
    items: PetMovement[];
    routes: Routes;
    activityNameById?: Record<string, string>;
    showActivity?: boolean;
}) {
    const t = useTranslations();
    const config = MOVEMENT_CONFIG[kind];
    const { Icon } = config;

    return (
        <div
            data-slot="dashboard-movement-card"
            className="flex flex-col gap-3 rounded-2xl border bg-card p-4"
        >
            <header className="flex items-center gap-2">
                <span
                    className={cn(
                        "flex items-center justify-center size-8 rounded-xl",
                        config.chipClass,
                    )}
                >
                    <Icon className={cn("size-4", config.iconClass)} />
                </span>
                <h3 className="flex-1 text-sm font-semibold">
                    {t(config.titleKey as Parameters<typeof t>[0])}
                </h3>
                <span className="rounded-full bg-muted px-2 py-0.5 text-xs font-semibold text-muted-foreground">
                    {items.length}
                </span>
            </header>

            {items.length === 0 ? (
                <p className="rounded-xl border border-dashed py-6 text-center text-xs text-muted-foreground">
                    {t(config.emptyKey as Parameters<typeof t>[0])}
                </p>
            ) : (
                <ul className="flex flex-col gap-2">
                    {items.map((movement) => (
                        <li key={`${movement.bookingId}-${movement.petId}`}>
                            <MovementRow
                                movement={movement}
                                routes={routes}
                                activityName={
                                    showActivity
                                        ? activityNameById?.[movement.activityId]
                                        : undefined
                                }
                            />
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function MovementRow({
    movement,
    routes,
    activityName,
}: {
    movement: PetMovement;
    routes: Routes;
    activityName?: string;
}) {
    const subtitle = movement.animalTypeName
        ? `${movement.animalTypeName} · ${movement.customerName}`
        : movement.customerName;

    return (
        <Link
            href={routes.BookingDetail({ id: movement.bookingId })}
            className="flex items-center gap-3 rounded-xl border p-2.5 transition-colors hover:bg-muted/40"
        >
            <span className="flex items-center justify-center size-10 rounded-lg bg-muted shrink-0">
                <AnimalGlyph
                    code={movement.animalTypeCode}
                    name={movement.animalTypeName}
                    className="size-5"
                />
            </span>
            <div className="flex flex-col min-w-0 flex-1">
                <span className="font-medium truncate leading-tight">{movement.petName}</span>
                <span className="text-xs text-muted-foreground truncate">{subtitle}</span>
            </div>
            {activityName && (
                <span className="max-w-28 shrink-0 truncate text-[11px] text-muted-foreground">
                    {activityName}
                </span>
            )}
        </Link>
    );
}

export function OccupancyCard({
    items,
    present,
    capacity,
    rate,
}: {
    items: HostingTodaySummary["occupancyByAnimal"];
    present: number;
    capacity: number;
    rate: number;
}) {
    const t = useTranslations();

    return (
        <div
            data-slot="dashboard-occupancy-card"
            className="flex flex-col gap-4 rounded-2xl border bg-card p-5"
        >
            <div className="flex items-center justify-between gap-2">
                <h3 className="text-sm font-semibold">
                    {t("features.hosting-today.sections.occupancy")}
                </h3>
                <span className="text-sm text-muted-foreground tabular-nums">
                    {present}/{capacity} · {rate}%
                </span>
            </div>

            {items.length === 0 ? (
                <p className="text-xs text-muted-foreground">
                    {t("features.hosting-today.sections.noOccupancy")}
                </p>
            ) : (
                <ul className="flex flex-col gap-3">
                    {items.map((animal) => (
                        <li key={animal.code} className="flex items-center gap-3">
                            <span className="flex items-center justify-center size-8 rounded-lg bg-muted shrink-0">
                                <AnimalGlyph
                                    code={animal.code}
                                    name={animal.name}
                                    className="size-4"
                                />
                            </span>
                            <div className="flex flex-1 flex-col gap-1 min-w-0">
                                <div className="flex items-center justify-between gap-2 text-xs">
                                    <span className="font-medium truncate">{animal.name}</span>
                                    <span className="shrink-0 text-muted-foreground tabular-nums">
                                        {animal.occupied}/{animal.capacity}
                                    </span>
                                </div>
                                <div className="h-1.5 rounded-full bg-muted overflow-hidden">
                                    <div
                                        className="h-full rounded-full bg-primary"
                                        style={{ width: `${Math.min(animal.rate, 100)}%` }}
                                    />
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function AnimalGlyph({
    code,
    name,
    className,
}: {
    code: string | null;
    name: string | null;
    className?: string;
}) {
    if (code && isIllustratedType(code)) {
        return <PetTypeIllustration code={code} name={name ?? ""} className={className} />;
    }
    return <PawPrint className={cn("text-muted-foreground", className)} />;
}

function monthShort(month: string, locale: string): string {
    const [year, monthIndex] = month.split("-").map(Number);
    return new Intl.DateTimeFormat(locale, { month: "short" }).format(
        new Date(year!, (monthIndex ?? 1) - 1, 1),
    );
}

export function DashboardSkeleton() {
    return (
        <div className="flex flex-col gap-4">
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                {Array.from({ length: 4 }).map((_, index) => (
                    <div key={index} className="h-24 rounded-2xl border bg-card animate-pulse" />
                ))}
            </div>
            <div className="h-56 rounded-2xl border bg-card animate-pulse" />
            <div className="grid gap-4 lg:grid-cols-2">
                <div className="h-40 rounded-2xl border bg-card animate-pulse" />
                <div className="h-40 rounded-2xl border bg-card animate-pulse" />
            </div>
            <div className="h-40 rounded-2xl border bg-card animate-pulse" />
        </div>
    );
}

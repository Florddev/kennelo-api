"use client";

import Link from "next/link";
import { useLocale, useTranslations } from "next-intl";
import {
    Building2,
    CalendarCheck,
    CalendarDays,
    ChevronRight,
    ClipboardList,
    Gauge,
    MapPin,
    PawPrint,
    Repeat,
    Sparkles,
    Users,
    Wallet,
} from "lucide-react";

import { formatAmount } from "@workspace/common";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import { Badge } from "@workspace/ui/components/badge";
import { Button } from "@workspace/ui/components/button";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { cn } from "@workspace/ui/lib/utils";
import type { ActivityModel } from "@workspace/modules/activities";

import { useNavigation } from "@/hooks/use-navigation";
import { useHostingToday } from "@/features/hosting/hooks/use-hosting-today";
import {
    DashboardSkeleton,
    MovementCard,
    OccupancyCard,
    RevenueCard,
    StatCard,
} from "@/features/hosting/components/dashboard-cards";
import { ActivityPageHeader } from "@/features/activities/components/activity-page-header";
import { useActivity } from "@/features/activities/hooks/use-activity";

const STATUS_BADGE_CLASS = {
    active: "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/40",
    inactive: "bg-zinc-500/15 text-zinc-700 dark:text-zinc-300 border-zinc-500/40",
} as const;

export default function ActivityInfoPage() {
    const t = useTranslations();
    const locale = useLocale();
    const { routes, params } = useNavigation<{ id: string }>();
    const { activity, isLoading: activityLoading } = useActivity(params.id);

    const weekStartsOn: 0 | 1 = locale === "en" ? 0 : 1;
    const data = useHostingToday([params.id], weekStartsOn);

    return (
        <div className="flex flex-col gap-6">
            <ActivityPageHeader>
                <Button asChild variant="flat" className="rounded-4xl">
                    <Link href={routes.ActivityBookings({ id: params.id })}>
                        {t("features.activities.manager.nav.bookings")}
                    </Link>
                </Button>
                <Button asChild className="rounded-4xl">
                    <Link href={routes.ActivitySettings({ id: params.id })}>
                        {t("ui.navigation.settings")}
                    </Link>
                </Button>
            </ActivityPageHeader>

            <HeroSection activity={activity} isLoading={activityLoading} />

            {data.isLoading ? (
                <DashboardSkeleton />
            ) : (
                <div className="flex flex-col gap-4">
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <StatCard
                            Icon={CalendarCheck}
                            label={t("features.hosting-today.stats.activeReservations")}
                            value={data.activeReservations}
                            subtitle={t("features.hosting-today.stats.pending", {
                                count: data.pendingReservations,
                            })}
                            tone="bg-sky-500/10 text-sky-600 dark:text-sky-400"
                            href={routes.ActivityBookings({ id: params.id })}
                        />
                        <StatCard
                            Icon={PawPrint}
                            label={t("features.hosting-today.stats.present")}
                            value={data.present}
                            subtitle={t("features.hosting-today.stats.capacity", {
                                count: data.capacity,
                            })}
                            tone="bg-primary/10 text-primary"
                        />
                        <StatCard
                            Icon={Gauge}
                            label={t("features.activities.dashboard.occupancyRate")}
                            value={`${data.occupancyRate}%`}
                            subtitle={`${data.present}/${data.capacity}`}
                            tone="bg-violet-500/10 text-violet-600 dark:text-violet-400"
                        />
                        <StatCard
                            Icon={Wallet}
                            label={t("features.activities.overview.revenueThisMonth")}
                            value={`${formatAmount(data.revenue.currentMonth)} €`}
                            subtitle={t("features.hosting-today.revenue.thisMonth")}
                            tone="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                        />
                    </div>

                    <RevenueCard revenue={data.revenue} locale={locale} />

                    <div className="grid gap-4 lg:grid-cols-2">
                        <MovementCard kind="arrival" items={data.arrivals} routes={routes} />
                        <MovementCard kind="departure" items={data.departures} routes={routes} />
                    </div>

                    <OccupancyCard
                        items={data.occupancyByAnimal}
                        present={data.present}
                        capacity={data.capacity}
                        rate={data.occupancyRate}
                    />

                    <QuickLinks id={params.id} />
                </div>
            )}
        </div>
    );
}

function HeroSection({
    activity,
    isLoading,
}: {
    activity: ActivityModel | null;
    isLoading: boolean;
}) {
    if (isLoading) {
        return <Skeleton className="h-24 w-full rounded-2xl" />;
    }
    if (!activity) {
        return <NotFound />;
    }
    return <ActivityHero activity={activity} />;
}

function ActivityHero({ activity }: { activity: ActivityModel }) {
    const t = useTranslations();
    const imageUrl = activity.getAvatarUrl();
    const statusKey = activity.isActive ? "active" : "inactive";

    return (
        <div
            data-slot="activity-overview-hero"
            className="flex flex-col gap-4 rounded-2xl border bg-card p-5 sm:flex-row sm:items-center"
        >
            <Avatar className="size-16 rounded-2xl overflow-hidden shrink-0">
                {imageUrl && (
                    <AvatarImage
                        src={imageUrl}
                        alt={activity.name}
                        className="object-cover rounded-none"
                    />
                )}
                <AvatarFallback className="rounded-2xl bg-muted">
                    <Building2 className="size-7 text-muted-foreground" />
                </AvatarFallback>
            </Avatar>

            <div className="flex min-w-0 flex-1 flex-col gap-2">
                <div className="flex flex-wrap items-center gap-2">
                    <h2 className="truncate text-lg font-semibold tracking-tight">
                        {activity.name}
                    </h2>
                    <Badge variant="outline" className={cn(STATUS_BADGE_CLASS[statusKey])}>
                        {t(`features.activities.status.${statusKey}`)}
                    </Badge>
                    {activity.type && (
                        <Badge variant="secondary" className="font-normal">
                            {t(`features.activities.types.${activity.type}`)}
                        </Badge>
                    )}
                </div>
                {activity.address && (
                    <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                        <MapPin className="size-3.5 shrink-0" />
                        <span className="truncate">{activity.address.getFullAddress()}</span>
                    </span>
                )}
            </div>
        </div>
    );
}

const QUICK_LINKS = [
    { key: "bookings", Icon: ClipboardList, route: "ActivityBookings" },
    { key: "availabilities", Icon: CalendarDays, route: "ActivityAvailabilities" },
    { key: "cycles", Icon: Repeat, route: "ActivityCycles" },
    { key: "services", Icon: Sparkles, route: "ActivityServices" },
    { key: "collaborators", Icon: Users, route: "ActivityCollaborators" },
] as const;

function QuickLinks({ id }: { id: string }) {
    const t = useTranslations();
    const { routes } = useNavigation();

    return (
        <div data-slot="activity-overview-quick-links" className="flex flex-col gap-3">
            <h3 className="text-sm font-semibold">
                {t("features.activities.overview.quickLinks")}
            </h3>
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {QUICK_LINKS.map(({ key, Icon, route }) => (
                    <Link
                        key={key}
                        href={routes[route]({ id })}
                        className="flex items-center gap-3 rounded-2xl border bg-card p-4 transition-colors hover:bg-muted/40"
                    >
                        <span className="flex items-center justify-center size-9 rounded-xl bg-muted shrink-0">
                            <Icon className="size-4 text-muted-foreground" />
                        </span>
                        <span className="flex-1 text-sm font-medium truncate">
                            {t(`features.activities.manager.nav.${key}`)}
                        </span>
                        <ChevronRight className="size-4 text-muted-foreground shrink-0" />
                    </Link>
                ))}
            </div>
        </div>
    );
}

function NotFound() {
    const t = useTranslations();
    return (
        <div className="flex flex-col items-center justify-center gap-3 py-16 text-center">
            <Building2 className="size-12 text-muted-foreground" />
            <h2 className="text-xl font-semibold">{t("features.activities.detail.notFound")}</h2>
            <p className="text-muted-foreground">
                {t("features.activities.detail.notFoundDescription")}
            </p>
        </div>
    );
}

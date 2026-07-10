"use client";

import { useMemo } from "react";
import Link from "next/link";
import { useLocale, useTranslations } from "next-intl";
import { SunFog } from "@solar-icons/react";
import { ArrowRightLeft, Building2, CalendarCheck, MessageSquare, PawPrint } from "lucide-react";

import { Button } from "@workspace/ui/components/button";

import PageLayout from "@/components/layouts/page-layout";
import { useAuth } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { useHostingToday } from "@/features/hosting/hooks/use-hosting-today";
import {
    DashboardSkeleton,
    MovementCard,
    OccupancyCard,
    RevenueCard,
    StatCard,
} from "@/features/hosting/components/dashboard-cards";

export default function HostingNowPage() {
    const t = useTranslations();
    const locale = useLocale();
    const { activities, isLoaded } = useAuth();
    const { routes } = useNavigation();

    const activityIds = useMemo(() => activities.map((activity) => activity.id), [activities]);
    const activityNameById = useMemo(() => {
        const map: Record<string, string> = {};
        activities.forEach((activity) => {
            map[activity.id] = activity.name;
        });
        return map;
    }, [activities]);

    const weekStartsOn: 0 | 1 = locale === "en" ? 0 : 1;
    const data = useHostingToday(activityIds, weekStartsOn);

    const dateLabel = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, {
                weekday: "long",
                day: "numeric",
                month: "long",
            }).format(new Date()),
        [locale],
    );

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
                    {t("features.hosting-today.noActivity.title")}
                </h2>
                <p className="text-sm text-muted-foreground max-w-sm">
                    {t("features.hosting-today.noActivity.description")}
                </p>
            </div>
        );
    }

    const movementsCount = data.arrivals.length + data.departures.length;
    const showActivity = activities.length > 1;

    return (
        <PageLayout
            Icon={SunFog}
            title={t("features.hosting-today.title")}
            containerClassName="p-4 md:p-8"
            headerTop={
                <Button asChild className="rounded-4xl gap-2" variant="flat">
                    <Link href={routes.MyActivities()}>{t("features.activities.manage")}</Link>
                </Button>
            }
        >
            <p className="text-muted-foreground capitalize -mt-2">{dateLabel}</p>

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
                            Icon={ArrowRightLeft}
                            label={t("features.hosting-today.stats.movements")}
                            value={movementsCount}
                            subtitle={t("features.hosting-today.stats.movementsDetail", {
                                arrivals: data.arrivals.length,
                                departures: data.departures.length,
                            })}
                            tone="bg-violet-500/10 text-violet-600 dark:text-violet-400"
                        />
                        <StatCard
                            Icon={MessageSquare}
                            label={t("features.hosting-today.stats.unread")}
                            value={data.unreadMessages}
                            subtitle={t("features.hosting-today.actions.seeMessages")}
                            tone="bg-amber-500/10 text-amber-600 dark:text-amber-400"
                            href={routes.HostingMessages()}
                        />
                    </div>

                    <RevenueCard revenue={data.revenue} locale={locale} />

                    <div className="grid gap-4 lg:grid-cols-2">
                        <MovementCard
                            kind="arrival"
                            items={data.arrivals}
                            routes={routes}
                            activityNameById={activityNameById}
                            showActivity={showActivity}
                        />
                        <MovementCard
                            kind="departure"
                            items={data.departures}
                            routes={routes}
                            activityNameById={activityNameById}
                            showActivity={showActivity}
                        />
                    </div>

                    <OccupancyCard
                        items={data.occupancyByAnimal}
                        present={data.present}
                        capacity={data.capacity}
                        rate={data.occupancyRate}
                    />
                </div>
            )}
        </PageLayout>
    );
}

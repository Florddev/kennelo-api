"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useTranslations } from "next-intl";
import { ArrowLeft, BillList, NotebookBookmark, Settings, Widget5 } from "@solar-icons/react";

import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";

import { useNavigation } from "@/hooks/use-navigation";
import { NavRow } from "@/components/navigation/nav-row";
import { SplitPageLayout, SplitPageLayoutNavItem } from "@/components/layouts/split-page-layout";
import { useIsMobile } from "@/hooks/use-mobile";
import { useActivity } from "@/features/activities";
import ActivityInfoPage from "./activity-info-page";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { isActiveSubRoute, subRouteOf } from "./route-utils";

export default function ActivityLayout({ children }: { children: React.ReactNode }) {
    const pathname = usePathname();
    const t = useTranslations();
    const { routes, params } = useNavigation<{ id: string }>();
    const isMobile = useIsMobile();

    const configurationNav: SplitPageLayoutNavItem[] = [
        {
            icon: Widget5,
            href: routes.ActivityOverview({ id: params.id }),
            label: t("ui.navigation.overview"),
            default: true,
        },
        {
            href: routes.ActivityBookings({ id: params.id }),
            label: t("features.activities.manager.nav.bookings"),
            icon: NotebookBookmark,
            default: false,
        },
        {
            href: routes.ActivityInvoices({ id: params.id }),
            label: t("features.activities.manager.nav.invoices"),
            icon: BillList,
            default: false,
            comingSoon: true,
        },
        {
            href: routes.ActivitySettings({ id: params.id }),
            label: t("ui.navigation.settings"),
            icon: Settings,
            default: false,
        },
    ];

    const activityNav: SplitPageLayoutNavItem[] = [];

    const allNavItems = [...configurationNav, ...activityNav];

    const currentSubRoute = subRouteOf(pathname);

    const isRoot = !allNavItems.some((item) => isActiveSubRoute(pathname, item.href));
    const currentPageLabel = allNavItems.find((item) =>
        isActiveSubRoute(pathname, item.href),
    )?.label;
    const { activity } = useActivity(params.id);

    const isSettingsPage =
        currentSubRoute === "/settings" || currentSubRoute.startsWith("/settings/");

    return (
        <SplitPageLayout isRoot={isRoot}>
            <SplitPageLayout.Sidebar className={cn("md:w-1/4", isSettingsPage && "hidden")}>
                <SplitPageLayout.Header>
                    <Button variant="flat" size={isMobile ? "icon-sm" : "default"} asChild>
                        <Link
                            href={
                                isRoot
                                    ? routes.MyActivities()
                                    : routes.ActivityDetails({ id: params.id })
                            }
                        >
                            <ArrowLeft className="size-4" />
                            <span className="hidden md:block">{t("common.actions.back")}</span>
                        </Link>
                    </Button>
                </SplitPageLayout.Header>

                <div className="px-4 md:px-0">
                    {activity ? (
                        <h1 className="font-semibold tracking-tight text-2xl md:text-3xl">
                            {!isRoot && <span className="md:hidden">{currentPageLabel}</span>}
                            <span className={cn(!isRoot && "md:block hidden")}>
                                {activity?.name}
                            </span>
                        </h1>
                    ) : (
                        <Skeleton className="h-8 w-3/4" />
                    )}
                </div>

                <SplitPageLayout.Nav>
                    {configurationNav.map((item) => (
                        <NavRow
                            key={item.href}
                            icon={item.icon}
                            label={item.label}
                            href={item.comingSoon ? "#" : item.href}
                            destructive={false}
                            displayArrow={!item.comingSoon}
                            disabled={item.comingSoon}
                            comingSoon={item.comingSoon}
                            className={cn(
                                "md:hover:bg-muted md:rounded-md md:p-4",
                                (isActiveSubRoute(pathname, item.href) ||
                                    (isRoot && item.default)) &&
                                    "md:bg-muted",
                            )}
                        />
                    ))}
                </SplitPageLayout.Nav>
            </SplitPageLayout.Sidebar>
            <SplitPageLayout.Content
                className={cn("md:w-3/4", isSettingsPage && "md:w-full md:p-0 gap-0")}
                defaultContent={<ActivityInfoPage />}
                cleanContainer
            >
                {children}
            </SplitPageLayout.Content>
        </SplitPageLayout>
    );
}

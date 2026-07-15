"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useTranslations } from "next-intl";
import {
    ArrowLeft,
    Calendar,
    Card,
    Paw,
    Star,
    UsersGroupTwoRounded,
    Widget5,
} from "@solar-icons/react";

import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";

import { useNavigation } from "@/hooks/use-navigation";
import { NavRow } from "@/components/navigation/nav-row";
import { SplitPageLayout, SplitPageLayoutNavItem } from "@/components/layouts/split-page-layout";
import { useIsMobile } from "@/hooks/use-mobile";
import { useActivity } from "@/features/activities";
import { Skeleton } from "@workspace/ui/components/skeleton";
import ActivityInformationsPage from "./informations/activity-informations-page";
import { isActiveSubRoute } from "../route-utils";

export default function ActivityLayout({ children }: { children: React.ReactNode }) {
    const pathname = usePathname();
    const t = useTranslations();
    const { routes, params } = useNavigation<{ id: string }>();
    const isMobile = useIsMobile();

    const settingsNav: SplitPageLayoutNavItem[] = [
        {
            icon: Widget5,
            href: routes.ActivitySettingsInformations({ id: params.id }),
            label: t("features.activities.manager.nav.info"),
            default: true,
        },
        {
            icon: Calendar,
            href: routes.ActivityAvailabilities({ id: params.id }),
            label: t("features.activities.manager.nav.availabilities"),
            default: false,
        },
        {
            icon: Paw,
            href: routes.ActivityCycles({ id: params.id }),
            label: t("features.activities.manager.nav.cycles"),
            default: false,
        },
        {
            icon: Star,
            href: routes.ActivityServices({ id: params.id }),
            label: t("features.activities.manager.nav.services"),
            default: false,
        },
        {
            href: routes.ActivityCollaborators({ id: params.id }),
            label: t("features.activities.manager.nav.collaborators"),
            icon: UsersGroupTwoRounded,
            default: false,
        },
        {
            href: routes.ActivityPayment({ id: params.id }),
            label: t("features.activities.manager.nav.payment"),
            icon: Card,
            default: false,
        },
    ];

    const isRoot = !settingsNav.some((item) => isActiveSubRoute(pathname, item.href));
    const currentPageLabel = settingsNav.find((item) =>
        isActiveSubRoute(pathname, item.href),
    )?.label;
    const { activity } = useActivity(params.id);

    return (
        <SplitPageLayout isRoot={isRoot}>
            <SplitPageLayout.Sidebar className="md:w-1/4">
                <SplitPageLayout.Header>
                    <Button variant="flat" size={isMobile ? "icon-sm" : "default"} asChild>
                        <Link
                            href={
                                isRoot || !isMobile
                                    ? routes.ActivityDetails({ id: params.id })
                                    : routes.ActivitySettings({ id: params.id })
                            }
                        >
                            <ArrowLeft className="size-4" />
                            <span className="hidden md:block">
                                {t("common.actions.backTo", { value: activity?.name ?? "" })}
                            </span>
                        </Link>
                    </Button>
                </SplitPageLayout.Header>

                <div className="px-4 md:px-0">
                    {activity ? (
                        <h1 className="font-semibold tracking-tight text-2xl md:text-3xl">
                            {!isRoot && <span className="md:hidden">{currentPageLabel}</span>}
                            <span className={cn(!isRoot && "md:block hidden")}>
                                {t("ui.navigation.settings")}
                            </span>
                        </h1>
                    ) : (
                        <Skeleton className="h-8 w-3/4" />
                    )}
                </div>

                <SplitPageLayout.Nav>
                    {settingsNav.map((item) => (
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
                className="md:w-3/4"
                defaultContent={<ActivityInformationsPage />}
            >
                {children}
            </SplitPageLayout.Content>
        </SplitPageLayout>
    );
}

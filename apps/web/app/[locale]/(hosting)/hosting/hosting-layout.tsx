"use client";

import { usePathname } from "next/navigation";
import { useTranslations } from "next-intl";
import {
    Calendar,
    ChatRoundLine,
    ChecklistMinimalistic,
    Scanner,
    SunFog,
} from "@solar-icons/react";
import { cn } from "@workspace/ui/lib/utils";

import { useNavigation } from "@/hooks/use-navigation";
import { NavRow } from "@/components/navigation/nav-row";
import { SplitPageLayout, SplitPageLayoutNavItem } from "@/components/layouts/split-page-layout";
import { Button } from "@workspace/ui/components/button";
import Link from "next/link";

export default function HostingLayout({ children }: { children: React.ReactNode }) {
    const pathname = usePathname();
    const t = useTranslations();
    const { routes } = useNavigation<{ id: string }>();

    const configurationNav: SplitPageLayoutNavItem[] = [
        {
            icon: SunFog,
            href: routes.HostingNow(),
            label: t("ui.navigation.hosting.today"),
            default: true,
        },
        {
            href: routes.HostingCalendar(),
            label: t("ui.navigation.hosting.calendar"),
            icon: Calendar,
            default: false,
        },
        {
            href: routes.HostingMessages(),
            label: t("ui.navigation.hosting.messages"),
            icon: ChatRoundLine,
            default: false,
            comingSoon: false,
        },
        {
            href: routes.MyActivities(),
            label: t("features.hosting-calendar.filters.activities"),
            icon: ChecklistMinimalistic,
            default: false,
            comingSoon: false,
        },
        {
            href: routes.HostingScan(),
            label: t("ui.navigation.hosting.scan"),
            icon: Scanner,
            default: false,
        },
        // {
        //     href: routes.HostingSubscription(),
        //     label: t('features.subscriptions.title'),
        //     icon: CrownLine,
        //     default: false,
        // }
    ];

    return (
        <SplitPageLayout isRoot={false}>
            <SplitPageLayout.Sidebar className={cn("hidden md:block w-full max-w-68 md:p-0")}>
                <div className="h-full flex flex-col justify-between w-full p-6">
                    <SplitPageLayout.Nav className="md:py-0 gap-1">
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
                                    pathname.includes(item.href) && "md:bg-muted",
                                )}
                            />
                        ))}
                    </SplitPageLayout.Nav>
                    <div
                        data-slot="host-verified-banner"
                        className="relative flex items-center gap-3 overflow-hidden rounded-3xl bg-secondary/20 p-4"
                    >
                        <span
                            aria-hidden
                            className="pointer-events-none absolute -top-5 -start-5 h-20 w-24 rounded-[50%] bg-secondary"
                        />
                        <div className="relative flex flex-1 flex-col gap-2">
                            <h3 className="text-lg font-semibold text-slate-900 whitespace-nowrap">
                                Passer à l&apos;abonnement <br /> supérieur !
                            </h3>
                            <p className="text-xs text-slate-700">
                                {t("features.profile.becomeHost.description")}
                            </p>
                            <Button size="sm" className="w-fit" asChild>
                                <Link href={routes.HostingSubscription()}>
                                    {t("common.actions.discover")}
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </SplitPageLayout.Sidebar>
            <SplitPageLayout.Content className={cn("md:w-full md:p-0")} cleanContainer>
                {children}
            </SplitPageLayout.Content>
        </SplitPageLayout>
    );
}

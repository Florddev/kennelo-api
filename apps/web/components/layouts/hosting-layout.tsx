"use client";

import { useLocale, useTranslations } from "next-intl";
import { usePathname } from "next/navigation";
import { Calendar, ChatRoundLine, Scanner, SunFog } from "@solar-icons/react";

import { cn } from "@workspace/ui/lib/utils";
import { isActivePath } from "@workspace/common";

import { useNavigation } from "@/hooks/use-navigation";
import { NavigationItem } from "@/components/navigation/nav-item";
import { EmailVerificationAlert } from "@/features/auth";
import { BottomNavbar } from "../navigation/navbar/bottom-navbar";
import { HostingNavbar } from "../navigation/navbar/hosting-navbar";

export default function HostingLayout({
    children,
    className,
}: {
    children: React.ReactNode;
    className?: string;
}) {
    const pathname = usePathname();
    const locale = useLocale();
    const { routes } = useNavigation();
    const t = useTranslations();
    const isActive = (href: string) => isActivePath(href, pathname, locale);

    const links = [
        {
            href: routes.HostingNow(),
            label: t("ui.navigation.hosting.today"),
            icon: SunFog,
            active: isActive(routes.HostingNow()),
        },
        {
            href: routes.HostingCalendar(),
            label: t("ui.navigation.hosting.calendar"),
            icon: Calendar,
            active: isActive(routes.HostingCalendar()),
        },
        {
            href: routes.HostingScan(),
            label: t("ui.navigation.hosting.scan"),
            icon: Scanner,
            active: isActive(routes.HostingScan()),
        },
        {
            href: routes.HostingMessages(),
            label: t("ui.navigation.hosting.messages"),
            icon: ChatRoundLine,
            active: isActive(routes.HostingMessages()),
        },
        // {
        //     href: routes.MyActivities(),
        //     label: t("ui.navigation.hosting.activity"),
        //     icon: NotesMinimalistic,
        //     active: isActive(routes.MyActivities()),
        // },

        // {
        //     href: routes.HostingSubscription(),
        //     label: t("ui.navigation.hosting.subscription"),
        //     icon: CrownLine,
        //     active: isActive(routes.HostingSubscription()),
        // },
    ];

    const mobileNavigationItems: NavigationItem[] = links.map(({ icon, label, href, active }) => ({
        icon,
        text: label,
        href,
        active,
    }));

    return (
        <div className={cn("bg-card min-h-[100dvh]")}>
            <HostingNavbar className="hidden md:flex" links={[] /*desktopLinks*/} />
            <EmailVerificationAlert
                title={t("features.auth.hostUnverified.title")}
                description={t("features.auth.hostUnverified.description")}
                className="px-4 pt-4 md:px-6"
            />
            <main className={cn("w-full h-full", className)}>{children}</main>
            {/* {isBottomNavbarVisible && ( */}
            <BottomNavbar className="block md:hidden" navigationItems={mobileNavigationItems} />
            {/* )} */}
        </div>
    );
}

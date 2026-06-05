"use client";

import { useLocale, useTranslations } from "next-intl";
import { usePathname } from "next/navigation";
import { Buildings, Calendar, ChatRoundLine, SunFog } from "@solar-icons/react";

import { cn } from "@workspace/ui/lib/utils";
import { isActivePath } from "@workspace/common";

import { useNavigation } from "@/hooks/use-navigation";
import { useNavVisibility } from "@/providers/navigation-visibility-provider";
import { NavigationItem } from "@/components/navigation/nav-item";
import { BottomNavbar } from "../navigation/navbar/bottom-navbar";
import { HostingNavbar } from "../navigation/navbar/hosting-navbar";

interface HostingLayoutProps {
    children: React.ReactNode;
    className?: string;
}

export default function HostingLayout({ children, className }: HostingLayoutProps) {
    const pathname = usePathname();
    const locale = useLocale();
    const { routes } = useNavigation();
    const t = useTranslations();
    const { isBottomNavbarVisible } = useNavVisibility();
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
            href: routes.MyEstablishments(),
            label: t("ui.navigation.hosting.establishment"),
            icon: Buildings,
            active: isActive(routes.MyEstablishments()),
        },
        {
            href: routes.HostingMessages(),
            label: t("ui.navigation.hosting.messages"),
            icon: ChatRoundLine,
            active: isActive(routes.HostingMessages()),
        },
    ];

    const desktopLinks = links.map(({ href, label, active, icon }) => ({
        href,
        label,
        active,
        icon,
    }));

    const mobileNavigationItems: NavigationItem[] = links.map(({ icon, label, href, active }) => ({
        icon,
        text: label,
        href,
        active,
    }));

    return (
        <div className={cn("bg-card min-h-[100dvh]")}>
            <HostingNavbar className="hidden md:flex" links={desktopLinks} />
            <main className={cn("w-full h-full", className)}>{children}</main>
            {isBottomNavbarVisible && (
                <BottomNavbar className="block md:hidden" navigationItems={mobileNavigationItems} />
            )}
        </div>
    );
}

"use client";

import { cn } from "@workspace/ui/lib/utils";
import { NavigationItem } from "@/components/navigation/nav-item";
import { usePathname } from "next/navigation";
import { useLocale, useTranslations } from "next-intl";
import { useNavigation } from "@/hooks/use-navigation";
import { BottomNavbar } from "../navigation/navbar/bottom-navbar";
import { MainNavbar } from "../navigation/navbar/main-navbar";
import {
    ChatRoundLine,
    FolderFavouriteStar,
    Hearts,
    MinimalisticMagnifier,
} from "@solar-icons/react";
import { isActivePath } from "@workspace/common";

import { useNavVisibility } from "@/providers/navigation-visibility-provider";
import { usePlatform } from "@/hooks/use-platform";

interface AppLayoutProps {
    children: React.ReactNode;
    className?: string;
}

export default function AppLayout({ children, className }: AppLayoutProps) {
    const pathname = usePathname();
    const locale = useLocale();
    const { routes } = useNavigation();
    const t = useTranslations();
    // const { isCapacitorApp } = usePlatform();

    const { isBottomNavbarVisible } = useNavVisibility();
    const isActive = (href: string) => isActivePath(href, pathname, locale);

    const navigationItems: NavigationItem[] = [
        // {
        //     icon: Home,
        //     text: t("ui.navigation.home"),
        //     href: routes.Home(),
        //     active: isActivePath(routes.Home()),
        // },
        {
            icon: MinimalisticMagnifier,
            text: t("ui.navigation.explore"),
            href: routes.Explore(),
            active: isActive(routes.Explore()),
        },
        {
            icon: FolderFavouriteStar,
            text: t("ui.navigation.favorites"),
            href: "#",
            active: isActive("#"),
        },
        {
            icon: Hearts,
            text: t("ui.navigation.pets"),
            href: routes.MyPets(),
            active: isActive(routes.MyPets()),
        },
        {
            icon: ChatRoundLine,
            text: t("ui.navigation.messages"),
            href: routes.Messages(),
            active: isActive(routes.Messages()),
        },
    ];

    return (
        <div className={cn("bg-card min-h-[100dvh]")}>
            {/* <div className="fixed bottom-8 right-8 flex flex-col items-end gap-4 z-20 sm:block w-fit">
                <div className="w-24 h-32 rounded-lg border-border shadow-lg mb-2">Chat box</div>
                <div className="h-fit w-fit">
                    <Image
                        className="w-14 shadow-md rounded-full"
                        src="/face.svg"
                        width={120}
                        height={100}
                        alt="Keny Face"
                    />
                </div>
            </div> */}

            <MainNavbar className="hidden md:block" navigationItems={navigationItems} />
            <main className={cn("w-full h-full", className)}>{children}</main>
            {isBottomNavbarVisible && (
                <BottomNavbar className="block md:hidden" navigationItems={navigationItems} />
            )}
        </div>
    );
}

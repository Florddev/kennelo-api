"use client";

import { cn } from "@workspace/ui/lib/utils";
import { useIsMobile } from "@/hooks/use-mobile";
import { KHome, KCompass, KMessage, KHeart } from "@workspace/ui/icons";
import { NavigationItem } from "@/components/navigation/nav-item";
import { usePathname } from "next/navigation";
import { useLocale, useTranslations } from "next-intl";
import { useNavigation } from "@/hooks/use-navigation";
import { BottomNavbar } from "../navigation/navbar/bottom-navbar";
import { MainNavbar } from "../navigation/navbar/main-navbar";

interface AppLayoutProps {
    children: React.ReactNode;
    className?: string;
}

export default function AppLayout({ children, className }: AppLayoutProps) {
    const isMobile = useIsMobile();
    const pathname = usePathname();
    const locale = useLocale();
    const { routes } = useNavigation();
    const t = useTranslations();

    const normalizePath = (path: string) => {
        const withoutQuery = path.split("?")[0] ?? "";
        const cleanPath = withoutQuery.split("#")[0] || "/";
        const localePrefix = `/${locale}`;
        const hasLocalePrefix =
            cleanPath === localePrefix || cleanPath.startsWith(`${localePrefix}/`);
        const noLocalePath = hasLocalePrefix
            ? cleanPath.slice(localePrefix.length) || "/"
            : cleanPath;

        let noTrailingSlash = noLocalePath;
        while (noTrailingSlash.length > 1 && noTrailingSlash.endsWith("/")) {
            noTrailingSlash = noTrailingSlash.slice(0, -1);
        }

        return noTrailingSlash;
    };

    const isActivePath = (href: string) => {
        const currentPath = normalizePath(pathname);
        const targetPath = normalizePath(href);

        if (targetPath === "/") {
            return currentPath === "/";
        }

        return currentPath === targetPath || currentPath.startsWith(`${targetPath}/`);
    };

    const navigationItems: NavigationItem[] = [
        {
            icon: KHome,
            text: t("ui.navigation.home"),
            href: routes.Home(),
            active: isActivePath(routes.Home()),
        },
        {
            icon: KCompass,
            text: t("ui.navigation.explore"),
            href: routes.Explore(),
            active: isActivePath(routes.Explore()),
        },
        {
            icon: KHeart,
            text: t("ui.navigation.pets"),
            href: routes.MyPets(),
            active: isActivePath(routes.MyPets()),
        },
        {
            icon: KMessage,
            text: t("ui.navigation.messages"),
            href: "/messages",
            active: isActivePath("/messages"),
        },
    ];

    return (
        <div className="bg-card min-h-screen">
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

            {!isMobile && <MainNavbar navigationItems={navigationItems} />}
            <main className={cn("w-full h-full", className)}>{children}</main>
            {isMobile && <BottomNavbar navigationItems={navigationItems} />}
        </div>
    );
}

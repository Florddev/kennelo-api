"use client";

import { cn } from "@workspace/ui/lib/utils";
import { useIsMobile } from "@/hooks/use-mobile";
import { KHome, KCompass, KMessage, KHeart } from "@workspace/ui/icons";
import { NavigationItem } from "@/components/navigation/nav-item";
import Image from "next/image";
import { useTranslations } from "next-intl";
import { useNavigation } from "@/hooks/use-navigation";
import { BottomNavbar } from "../navigation/navbar/bottom-navbar";
import { MainNavbar } from "../navigation/navbar/main-navbar";

interface AppLayoutProps {
    children: React.ReactNode;
    className?: string;
}

export default function AppLayout({ children, className }: AppLayoutProps) {
    const isMobile = useIsMobile();
    const { routes } = useNavigation();
    const t = useTranslations();

    const navigationItems: NavigationItem[] = [
        {
            icon: KHome,
            text: t("ui.navigation.home"),
            active: true,
            href: "/",
        },
        {
            icon: KCompass,
            text: t("ui.navigation.explore"),
            active: false,
            href: "/explore",
        },
        {
            icon: KHeart,
            text: t("ui.navigation.pets"),
            active: false,
            href: routes.MyPets(),
        },
        {
            icon: KMessage,
            text: t("ui.navigation.messages"),
            active: false,
            href: "/messages",
        },
    ];

    return (
        <div className="bg-background h-full">
            <div className="fixed bottom-8 right-8 flex flex-col items-end gap-4 z-20 hidden sm:block">
                <div className="hidden">{/* Chat box */}</div>
                <div className="h-fit w-fit">
                    <Image
                        className="w-14 shadow-md rounded-full"
                        src="/face.svg"
                        width={120}
                        height={100}
                        alt="Keny Face"
                    />
                </div>
            </div>

            {!isMobile && <MainNavbar navigationItems={navigationItems} />}
            <main className={cn("w-full h-full", className)}>{children}</main>
            {isMobile && <BottomNavbar navigationItems={navigationItems} />}
        </div>
    );
}

"use client";

import { cn } from "@workspace/ui/lib/utils";
import { useIsMobile } from "@/hooks/use-mobile";
import { KHome, KCompass, KMessage, KHeart } from "@workspace/ui/components/icons";
import type { KIcon } from "@workspace/ui/components/icons";
import NavButton from "@/components/navigation/nav-button";
import NavItem from "@/components/navigation/nav-item";
import UserMenu from "@/components/navigation/user-menu";
import Image from "next/image";
import { useAuth } from "@/features/auth";
import { useTranslations } from "next-intl";
import Link from "next/link";
import { useNavigation } from "@/hooks/use-navigation";
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from "@workspace/ui/components/input-group";
import { Search } from "lucide-react";

interface NavigationItem {
    icon: KIcon;
    text: string;
    active: boolean;
    href: string;
    special?: boolean;
}

interface AppLayoutProps {
    children: React.ReactNode;
    className?: string;
}

function useHostSpaceHref() {
    const { routes } = useNavigation();
    const { establishments, hasEstablishment } = useAuth();

    if (!hasEstablishment) return undefined;
    if (establishments.length === 1) {
        return routes.EstablishmentDetail({ id: establishments[0]!.id });
    }
    return routes.MyEstablishments();
}

function DesktopHeader({ navigationItems }: { navigationItems: NavigationItem[] }) {
    const { routes } = useNavigation();
    const { user, isAuthenticated, isLoaded, hasEstablishment, logout } = useAuth();
    const t = useTranslations();
    const hostSpaceHref = useHostSpaceHref();

    return (
        <header className="sticky top-0 start-0 w-full h-16 flex items-center z-20 bg-background/60 backdrop-blur-sm">
            <div className="container mx-auto h-full flex justify-between items-center px-4">
                <div className="flex justify-start items-center w-full max-w-xs">
                    <InputGroup className="rounded-full px-1.5 !py-5 w-full border border-primary/20 bg-white/30 backdrop-blur-sm">
                        <InputGroupInput
                            placeholder={t("common.placeholders.search-for-a-pension")}
                            className="px-2 placeholder:text-base"
                        />
                        <InputGroupAddon align="inline-end" className="p-0">
                            <InputGroupButton
                                size="icon-sm"
                                className="rounded-full h-8 aspect-square"
                                variant="default"
                            >
                                <Search size={18} />
                            </InputGroupButton>
                        </InputGroupAddon>
                    </InputGroup>
                </div>
                <Link
                    href={routes.Home()}
                    className="relative w-full h-full flex justify-center items-center font-semibold text-lg"
                >
                    <Image
                        className="object-cover max-h-full h-1/2 w-auto"
                        src="/logo_type.svg"
                        height={120}
                        width={30}
                        alt="Kennelo logo"
                    />
                </Link>
                <div className="flex justify-end items-center">
                    {!isAuthenticated ? (
                        <div className="flex gap-1">
                            <NavButton>{t("common.actions.bookOnline")}</NavButton>
                        </div>
                    ) : (
                        <div className="flex items-center gap-2">
                            {isLoaded && !hasEstablishment && (
                                <Link href={routes.BecomeHost()}>
                                    <NavButton variant="ghost">
                                        {t("common.actions.becomeHost")}
                                    </NavButton>
                                </Link>
                            )}
                            {isLoaded && hasEstablishment && hostSpaceHref && (
                                <Link href={hostSpaceHref}>
                                    <NavButton variant="ghost">
                                        {t("common.actions.hostSpace")}
                                    </NavButton>
                                </Link>
                            )}
                            {navigationItems.map((item) => (
                                <NavItem
                                    key={item.href}
                                    Icon={item.icon}
                                    iconSize={24}
                                    active={item.active}
                                    iconSecondaryOpacity={1}
                                    href={item.href}
                                    className="mt-1"
                                >
                                    {item.text}
                                </NavItem>
                            ))}
                            <UserMenu
                                user={user ?? undefined}
                                hasEstablishment={hasEstablishment}
                                hostSpaceHref={hostSpaceHref}
                                onLogout={logout}
                            />
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}

export default function AppLayout({ children, className }: AppLayoutProps) {
    const isMobile = useIsMobile();
    const { routes } = useNavigation();
    const { user, isAuthenticated, logout } = useAuth();
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
        <div className="bg-background h-full relative">
            <div className="fixed bottom-8 end-8 flex flex-col items-end gap-4 z-20">
                <div className="hidden" />
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

            {!isMobile && <DesktopHeader navigationItems={navigationItems} />}
            <main className={cn("relative h-full", className)}>{children}</main>
            {isMobile && (
                <nav className="fixed bottom-0 w-full h-16 bg-background border-t border-primary/10 flex items-center z-10 pb-3">
                    <div className="container mx-auto h-full flex justify-between items-center px-4 sm:px-6">
                        {navigationItems.map((item) => (
                            <NavItem
                                key={item.href}
                                Icon={item.icon}
                                iconSize={item.special ? 24 : 28}
                                active={item.active}
                                iconSecondaryOpacity={item.active ? 1 : undefined}
                                href={item.href}
                                className={cn(
                                    "mt-1 text-xs text-muted-foreground",
                                    item.special &&
                                        "mt-0 px-0 bg-primary text-primary-foreground rounded-full aspect-square h-10",
                                )}
                            >
                                {!item.special && item.text}
                            </NavItem>
                        ))}
                        {isAuthenticated && (
                            <div className="flex flex-col items-center gap-0.5 mt-1">
                                <UserMenu user={user ?? undefined} onLogout={logout} />
                                <span className="text-xs text-muted-foreground">
                                    {t("ui.navigation.profile")}
                                </span>
                            </div>
                        )}
                    </div>
                </nav>
            )}
        </div>
    );
}

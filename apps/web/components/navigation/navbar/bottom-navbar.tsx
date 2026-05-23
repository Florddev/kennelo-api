"use client";

import { useAuth } from "@/features/auth";
import NavItem, { NavigationItem } from "../nav-item";
import { useLocale, useTranslations } from "next-intl";
import { cn } from "@workspace/ui/lib/utils";
import { usePlatform } from "@/hooks/use-platform";
import { UserCircle } from "@solar-icons/react";
import { UserAvatar } from "@/features/auth/components/user-avatar";
import { usePathname } from "next/navigation";
import { isActivePath } from "@workspace/common";
import { useNavigation } from "@/hooks/use-navigation";

export function BottomNavbar({
    navigationItems,
    className,
}: {
    navigationItems: NavigationItem[];
    className?: string;
}) {
    const { user, isAuthenticated } = useAuth();
    const { isCapacitorApp } = usePlatform();
    const { routes } = useNavigation();
    const t = useTranslations();

    const pathname = usePathname();
    const locale = useLocale();

    const isActive = (href: string) => isActivePath(href, pathname, locale);

    return (
        <nav
            className={cn(
                "fixed bottom-0 w-full bg-card border-t border-primary/10 flex items-start z-10",
                isCapacitorApp ? "pb-2.5" : "pb-0",
                className,
            )}
        >
            <div className="container mx-auto h-fit grid grid-cols-5 justify-around w-full items-center py-1.5">
                {navigationItems.map((item) => (
                    <NavItem
                        key={item.href}
                        Icon={item.icon}
                        iconSize={26}
                        active={item.active}
                        href={item.href}
                        className={cn(
                            "text-muted-foreground text-xs",
                            item.active && "text-primary",
                        )}
                    >
                        {item.text}
                    </NavItem>
                ))}
                {isAuthenticated ? (
                    <NavItem
                        iconSize={26}
                        href={routes.Profile()}
                        active={isActive(routes.Profile())}
                        className="text-muted-foreground text-xs"
                    >
                        <UserAvatar
                            user={user}
                            className={cn(
                                "size-[28px] border-1 border-white",
                                isActive(routes.Profile()) && "ring-1 ring-primary rounded-full",
                            )}
                        />
                        {t("ui.navigation.profile")}
                    </NavItem>
                ) : (
                    <NavItem
                        Icon={UserCircle}
                        iconSize={26}
                        href={routes.Login()}
                        active={isActive(routes.Login())}
                        className="text-muted-foreground text-xs"
                    >
                        {t("common.actions.login")}
                    </NavItem>
                )}
            </div>
        </nav>
    );
}

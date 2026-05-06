"use client";

import { useAuth } from "@/features/auth";
import NavItem, { NavigationItem } from "../nav-item";
import { useLocale, useTranslations } from "next-intl";
import { cn } from "@workspace/ui/lib/utils";
import { usePlatform } from "@/hooks/use-platform";
import { routes } from "@/lib/routes";
import { UserCircle } from "@solar-icons/react";
import { UserAvatar } from "@/features/auth/components/user-avatar";
import { usePathname } from "next/navigation";
import { isActivePath } from "@workspace/common";

export function BottomNavbar({
    navigationItems,
    className,
}: {
    navigationItems: NavigationItem[];
    className?: string;
}) {
    const { user, isAuthenticated } = useAuth();
    const { isCapacitorApp } = usePlatform();
    const t = useTranslations();

    const pathname = usePathname();
    const locale = useLocale();

    const isActive = (href: string) => isActivePath(href, pathname, locale);

    return (
        <nav
            className={cn(
                "fixed bottom-0 w-full bg-card border-t border-primary/10 flex items-start z-10",
                isCapacitorApp ? "h-14.5" : "h-12",
                className,
            )}
        >
            <div className="container mx-auto h-fit flex justify-around w-full items-center py-0.5">
                {navigationItems.map((item) => (
                    <NavItem
                        key={item.href}
                        Icon={item.icon}
                        iconSize={24}
                        active={item.active}
                        href={item.href}
                        className={cn(
                            "mt-1 text-xs text-muted-foreground gap-0.5 max-w-1/5",
                            item.active && "text-primary",
                        )}
                    >
                        {item.text}
                    </NavItem>
                ))}
                {isAuthenticated ? (
                    <NavItem
                        iconSize={24}
                        href={routes.Profile()}
                        active={isActive(routes.Profile())}
                        className={cn("text-xs text-muted-foreground gap-0.5 max-w-1/5")}
                    >
                        <UserAvatar user={user} className="size-[26px]" />
                        {t("ui.navigation.profile")}
                    </NavItem>
                ) : (
                    <NavItem
                        Icon={UserCircle}
                        iconSize={24}
                        href={routes.Login()}
                        active={isActive(routes.Login())}
                        className={cn("mt-1 text-xs text-muted-foreground gap-0.5 max-w-1/5")}
                    >
                        {t("common.actions.login")}
                    </NavItem>
                )}
            </div>
        </nav>
    );
}

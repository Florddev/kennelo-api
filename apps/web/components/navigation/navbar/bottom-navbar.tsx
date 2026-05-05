"use client";

import { useAuth } from "@/features/auth";
import NavItem, { NavigationItem } from "../nav-item";
import UserMenu from "../user-menu";
import { useTranslations } from "next-intl";
import { cn } from "@workspace/ui/lib/utils";
import { usePlatform } from "@/hooks/use-platform";
import { routes } from "@/lib/routes";
import { UserCircle } from "@solar-icons/react";

export function BottomNavbar({
    navigationItems,
    className,
}: {
    navigationItems: NavigationItem[];
    className?: string;
}) {
    const { user, isAuthenticated, logout } = useAuth();
    const { isCapacitorApp } = usePlatform();
    const t = useTranslations();

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
                    <div className="flex flex-col items-center gap-0.5 mt-1">
                        <UserMenu user={user ?? undefined} onLogout={logout} />
                        <span className="text-xs font-medium text-muted-foreground/80">
                            {t("ui.navigation.profile")}
                        </span>
                    </div>
                ) : (
                    <NavItem
                        Icon={UserCircle}
                        iconSize={24}
                        href={routes.Login()}
                        className={cn("mt-1 text-xs text-muted-foreground gap-0.5 max-w-1/5")}
                    >
                        {t("common.actions.login")}
                    </NavItem>
                )}
            </div>
        </nav>
    );
}

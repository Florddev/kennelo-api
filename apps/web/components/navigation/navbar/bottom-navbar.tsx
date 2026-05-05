"use client";

import { useAuth } from "@/features/auth";
import NavItem, { NavigationItem } from "../nav-item";
import UserMenu from "../user-menu";
import { useTranslations } from "next-intl";
import { cn } from "@workspace/ui/lib/utils";
import { usePlatform } from "@/hooks/use-platform";

export function BottomNavbar({ navigationItems }: { navigationItems: NavigationItem[] }) {
    const { user, isAuthenticated, logout } = useAuth();
    const { isCapacitorApp } = usePlatform();
    const t = useTranslations();

    return (
        <nav
            className={cn(
                "fixed bottom-0 w-full bg-card border-t border-primary/10 flex items-center z-10",
                isCapacitorApp ? "pb-3 h-16" : "pb-0.5 h-13",
            )}
        >
            <div className="container mx-auto h-full flex justify-around w-full items-center">
                {navigationItems.map((item) => (
                    <NavItem
                        key={item.href}
                        Icon={item.icon}
                        iconSize={26}
                        active={item.active}
                        href={item.href}
                        className={cn(
                            "mt-1 text-xs text-muted-foreground gap-0.5",
                            item.active && "text-primary",
                        )}
                    >
                        {item.text}
                    </NavItem>
                ))}
                {isAuthenticated && (
                    <div className="flex flex-col items-center gap-0.5 mt-1">
                        <UserMenu user={user ?? undefined} onLogout={logout} />
                        <span className="text-xs font-medium text-muted-foreground/80">
                            {t("ui.navigation.profile")}
                        </span>
                    </div>
                )}
            </div>
        </nav>
    );
}

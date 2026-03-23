"use client";

import { useAuth } from "@/features/auth";
import NavItem, { NavigationItem } from "../nav-item";
import UserMenu from "../user-menu";
import { useTranslations } from "next-intl";
import { cn } from "@workspace/ui/lib/utils";

export function BottomNavbar({ navigationItems }: { navigationItems: NavigationItem[] }) {
    const { user, isAuthenticated, logout } = useAuth();
    const t = useTranslations();

    return (
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
                        <span className="text-xs font-medium text-muted-foreground">
                            {t("ui.navigation.profile")}
                        </span>
                    </div>
                )}
            </div>
        </nav>
    );
}

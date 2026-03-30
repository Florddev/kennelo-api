"use client";

import { cn } from "@workspace/ui/lib/utils";
import NavItem, { NavigationItem } from "../nav-item";
import { useTranslations } from "next-intl";
import { useScrolled } from "@/hooks/use-scrolled";
import { useAuth } from "@/features/auth";
import { LanguageSwitcher } from "@/components/i18n/language-switcher";
import Link from "next/link";
import Image from "next/image";
import NavButton from "../nav-button";
import UserMenu from "../user-menu";
import { useNavigation } from "@/hooks/use-navigation";

export function MainNavbar({ navigationItems }: { navigationItems: NavigationItem[] }) {
    const { user, isAuthenticated, isLoaded, hasEstablishment, logout } = useAuth();
    const { routes } = useNavigation();
    const scrolled = useScrolled(0);
    const t = useTranslations();

    return (
        <header
            className={cn(
                "sticky top-0 left-0 w-full h-16 flex items-center z-20 transition-backdrop transition-background duration-150",
                scrolled
                    ? "bg-background/90 backdrop-blur-sm border-border/50 border-b"
                    : "border-b border-transparent",
            )}
        >
            <div className="mx-auto h-full flex justify-between items-center w-full px-8">
                <div className="max-w-sm w-full">
                    <LanguageSwitcher showDetails />
                </div>

                <Link
                    href={routes.Home()}
                    className="relative w-full max-w-xs h-full flex justify-center items-center font-semibold text-lg"
                >
                    <Image
                        className="object-cover max-h-full h-1/2 w-auto"
                        src="/logo_type.svg"
                        height={120}
                        width={30}
                        alt="Kennelo logo"
                    />
                </Link>
                <div className="flex justify-end items-center max-w-sm w-full">
                    {!isAuthenticated ? (
                        <div className="flex gap-1">
                            <Link
                                href={
                                    isAuthenticated
                                        ? routes.BecomeHost()
                                        : routes.Login({
                                              search_params: {
                                                  redirect_url: encodeURI(routes.BecomeHost()),
                                              },
                                          })
                                }
                            >
                                <NavButton variant="ghost">
                                    {t("common.actions.becomeHost")}
                                </NavButton>
                            </Link>
                            <NavButton asChild>
                                <Link href={routes.Login()}>{t("ui.navigation.bookOnline")}</Link>
                            </NavButton>
                        </div>
                    ) : (
                        <div className="flex items-center gap-3">
                            {isLoaded && !hasEstablishment && (
                                <Link href={routes.BecomeHost()}>
                                    <NavButton variant="ghost">
                                        {t("common.actions.becomeHost")}
                                    </NavButton>
                                </Link>
                            )}
                            <div className="flex items-center gap-1.5">
                                {navigationItems.map((item) => (
                                    <NavItem
                                        key={item.href}
                                        Icon={item.icon}
                                        iconSize={28}
                                        active={item.active}
                                        iconSecondaryOpacity={item.active ? 1 : 0.8}
                                        href={item.href}
                                        className="mt-1"
                                    >
                                        {item.text}
                                    </NavItem>
                                ))}
                                <UserMenu
                                    user={user ?? undefined}
                                    onLogout={logout}
                                    hasEstablishment={hasEstablishment}
                                />
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}

"use client";

import Link from "next/link";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from "@workspace/ui/components/dropdown-menu";
import { Languages, CheckIcon } from "lucide-react";
import { useTheme } from "next-themes";
import { useLocale, useTranslations } from "next-intl";
import type { Locale } from "@/dictionaries";
import { LanguageSelectorItems } from "../i18n/language-selector";
import { routes } from "@/lib/routes";
import { useAuth } from "@/features/auth";
import { cn } from "@workspace/ui/lib/utils";
import {
    Bell,
    Buildings,
    Logout2,
    Monitor,
    Moon,
    Settings,
    SolarProvider,
    Sun,
    UserCircle,
} from "@solar-icons/react";
import { UserAvatar } from "@/features/auth/components/user-avatar";
import { UserModel } from "@workspace/modules/users";

export default function UserMenu({
    user,
    hasActivity,
    className,
}: {
    user?: UserModel;
    hasActivity?: boolean;
    className?: string;
}) {
    const { theme, setTheme } = useTheme();
    const { logout } = useAuth();
    const locale = useLocale() as Locale;
    const t = useTranslations();

    const themeOptions = [
        { value: "light", label: t("ui.navigation.theme_light"), icon: Sun },
        { value: "dark", label: t("ui.navigation.theme_dark"), icon: Moon },
        { value: "system", label: t("ui.navigation.theme_system"), icon: Monitor },
    ];

    return (
        <SolarProvider value={{ weight: "Outline" }}>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <button
                        className={cn(
                            "relative rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2",
                            className,
                        )}
                    >
                        <UserAvatar user={user} className="size-full" />
                    </button>
                </DropdownMenuTrigger>

                <DropdownMenuContent className="w-48" align="end" sideOffset={8}>
                    <DropdownMenuGroup>
                        <DropdownMenuItem asChild>
                            <Link href={`/${locale}/profile`} className="cursor-pointer">
                                <UserCircle className="h-4 w-4" />
                                <span>{t("ui.navigation.my-profile")}</span>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={`/${locale}/notifications`} className="cursor-pointer">
                                <Bell className="h-4 w-4" />
                                <span>{t("ui.navigation.notifications")}</span>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={routes.MyProfileAbout()} className="cursor-pointer">
                                <Settings className="h-4 w-4" />
                                <span>{t("ui.navigation.settings")}</span>
                            </Link>
                        </DropdownMenuItem>
                        {hasActivity && (
                            <DropdownMenuItem asChild>
                                <Link href={routes.HostingNow()} className="cursor-pointer">
                                    <Buildings className="h-4 w-4" />
                                    <span>{t("common.actions.hostSpace")}</span>
                                </Link>
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuGroup>

                    <DropdownMenuSeparator />

                    <DropdownMenuLabel className="font-normal">
                        {t("ui.navigation.preferences")}
                    </DropdownMenuLabel>
                    <DropdownMenuSub>
                        <DropdownMenuSubTrigger>
                            <Monitor className="h-4 w-4" />
                            <span>{t("ui.navigation.theme")}</span>
                        </DropdownMenuSubTrigger>
                        <DropdownMenuSubContent>
                            <DropdownMenuGroup>
                                <DropdownMenuLabel className="font-normal">
                                    {t("ui.navigation.appearance")}
                                </DropdownMenuLabel>
                                {themeOptions.map((option) => {
                                    const Icon = option.icon;
                                    const isActive = theme === option.value;
                                    return (
                                        <DropdownMenuItem
                                            key={option.value}
                                            onClick={() => setTheme(option.value)}
                                        >
                                            <Icon className="h-4 w-4" />
                                            <span className="flex-1">{option.label}</span>
                                            {isActive && <CheckIcon className="h-4 w-4 ms-auto" />}
                                        </DropdownMenuItem>
                                    );
                                })}
                            </DropdownMenuGroup>
                        </DropdownMenuSubContent>
                    </DropdownMenuSub>

                    <DropdownMenuSub>
                        <DropdownMenuSubTrigger>
                            <Languages className="h-4 w-4" />
                            <span>{t("ui.navigation.language")}</span>
                        </DropdownMenuSubTrigger>
                        <DropdownMenuSubContent>
                            <LanguageSelectorItems />
                        </DropdownMenuSubContent>
                    </DropdownMenuSub>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem variant="destructive" onClick={logout}>
                        <Logout2 className="h-4 w-4" />
                        <span>{t("features.auth.logout")}</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SolarProvider>
    );
}

"use client";

import Link from "next/link";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
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
import {
    Settings,
    Bell,
    LogOut,
    Moon,
    Sun,
    Monitor,
    Languages,
    UserCircle,
    CheckIcon,
    Building2,
} from "lucide-react";
import { useTheme } from "next-themes";
import { useLocale, useTranslations } from "next-intl";
import type { Locale } from "@/dictionaries";
import { LanguageSelectorItems } from "../i18n/language-selector";
import { routes } from "@/lib/routes";
import { useNavigation } from "@/hooks/use-navigation";
import { useAuth } from "@/features/auth";
import { cn } from "@workspace/ui/lib/utils";

interface UserMenuProps {
    user?: {
        avatarUrl?: string | null;
        getFullName: () => string;
        getInitials: () => string;
        email?: string | null;
    };
    hasEstablishment?: boolean;
    onLogout?: () => void;
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

export default function UserMenu({ user, hasEstablishment, onLogout, className }: UserMenuProps) {
    const { theme, setTheme } = useTheme();
    const locale = useLocale() as Locale;
    const hostSpaceHref = useHostSpaceHref();
    const t = useTranslations();

    const themeOptions = [
        { value: "light", label: t("ui.navigation.theme_light"), icon: Sun },
        { value: "dark", label: t("ui.navigation.theme_dark"), icon: Moon },
        { value: "system", label: t("ui.navigation.theme_system"), icon: Monitor },
    ];

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button className="relative rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                    <Avatar className={cn("cursor-pointer size-[32px]", className)}>
                        <AvatarImage
                            src={user?.avatarUrl || undefined}
                            alt={user?.getFullName() || "User profile"}
                        />
                        <AvatarFallback>{user?.getInitials() || "U"}</AvatarFallback>
                    </Avatar>
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
                    {hasEstablishment && hostSpaceHref && (
                        <DropdownMenuItem asChild>
                            <Link href={hostSpaceHref} className="cursor-pointer">
                                <Building2 className="h-4 w-4" />
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

                <DropdownMenuItem variant="destructive" onClick={onLogout}>
                    <LogOut className="h-4 w-4" />
                    <span>{t("features.auth.logout")}</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

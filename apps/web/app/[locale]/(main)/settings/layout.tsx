"use client";

import { cn } from "@workspace/ui/lib/utils";
import { Badge } from "@workspace/ui/components/badge";
import { Card, CardContent } from "@workspace/ui/components/card";
import { Input } from "@workspace/ui/components/input";
import { Button } from "@workspace/ui/components/button";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useTranslations } from "next-intl";
import { routes } from "@/lib/routes";
import {
    ArrowLeft,
    Bell,
    LetterUnread,
    MinimalisticMagnifier,
    Password,
    UserCircle,
} from "@solar-icons/react";
import { useScrolled } from "@/hooks/use-scrolled";
import { NavRow } from "@/components/navigation/nav-row";

export default function ProfileSettingsLayout({ children }: { children: React.ReactNode }) {
    const pathname = usePathname();
    const t = useTranslations();
    const scrolled = useScrolled(100);

    const settingsNav = [
        {
            icon: UserCircle,
            label: t("ui.navigation.personalInformation"),
            href: routes.MyProfileAbout(),
            comingSoon: false,
        },
        {
            icon: Password,
            label: t("ui.navigation.changePassword"),
            href: routes.MyProfileChangePassword(),
            comingSoon: false,
        },
        {
            icon: LetterUnread,
            label: t("ui.navigation.emailPreferences"),
            href: routes.MyProfileEmailPreferences(),
            comingSoon: true,
        },
        {
            icon: Bell,
            label: t("ui.navigation.notificationPreferences"),
            href: routes.MyProfilePreferencesNotification(),
            comingSoon: true,
        },
    ];

    const isSettingsRoot = !settingsNav.some((item) => pathname.includes(item.href));
    const currentPageLabel = settingsNav.find((item) => pathname.includes(item.href))?.label;

    return (
        <div className="md:px-12">
            <div className="flex flex-col pb-2 md:h-18">
                <div
                    className={cn(
                        "bg-card flex justify-between px-4 py-2 w-full fixed top-0 z-10",
                        scrolled && "border-b",
                        !isSettingsRoot && "md:hidden",
                    )}
                >
                    <Button variant="flat" size="icon-sm" asChild>
                        <Link href={isSettingsRoot ? routes.Profile() : routes.Settings()}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                </div>
                <div
                    className={cn(
                        "flex items-center justify-between w-full px-4 pt-12 md:py-4 md:px-0",
                    )}
                >
                    <h1 className="font-semibold tracking-tight text-2xl md:text-3xl">
                        {!isSettingsRoot && <span className="md:hidden">{currentPageLabel}</span>}
                        <span className={cn(!isSettingsRoot && "hidden md:block")}>
                            {t("ui.navigation.profileSettings")}
                        </span>
                    </h1>
                    <div className="hidden md:flex relative w-64">
                        <MinimalisticMagnifier className="absolute start-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                        <Input className="ps-9" placeholder={t("common.placeholders.search")} />
                    </div>
                </div>
            </div>

            <div className="flex gap-6">
                <aside className="hidden md:block relative min-w-64 shrink-0 py-6 pt-0">
                    <nav className="sticky top-22 flex flex-col gap-1">
                        {settingsNav.map((item) => {
                            const isActive = pathname.includes(item.href);
                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={cn(
                                        "rounded-lg px-3 py-2 text-sm transition-colors",
                                        isActive
                                            ? "bg-muted text-foreground font-medium"
                                            : "text-muted-foreground hover:bg-muted/50 hover:text-foreground",
                                    )}
                                >
                                    {item.label}
                                    {item.comingSoon && (
                                        <Badge variant="destructive" className="ms-2">
                                            {t("ui.navigation.comingSoon")}
                                        </Badge>
                                    )}
                                </Link>
                            );
                        })}
                    </nav>
                </aside>

                <main className="flex-1 md:py-6 md:pt-0">
                    <div
                        className={cn(
                            "md:hidden flex flex-col gap-2 px-4 pb-4",
                            !isSettingsRoot && "hidden",
                        )}
                    >
                        <Card className="p-0 ring-0">
                            <CardContent className="p-0">
                                {settingsNav.map((item) => (
                                    <NavRow
                                        key={item.href}
                                        icon={item.icon}
                                        label={item.label}
                                        href={item.comingSoon ? "#" : item.href}
                                        destructive={false}
                                        displayArrow={!item.comingSoon}
                                        disabled={item.comingSoon}
                                        badge={
                                            item.comingSoon && (
                                                <Badge variant="destructive">
                                                    {t("ui.navigation.comingSoon")}
                                                </Badge>
                                            )
                                        }
                                    />
                                ))}
                            </CardContent>
                        </Card>
                    </div>

                    <div
                        className={cn(
                            isSettingsRoot && "hidden md:block",
                            !isSettingsRoot && "px-4 md:p-0",
                        )}
                    >
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}

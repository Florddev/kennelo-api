"use client";

import { cn } from "@workspace/ui/lib/utils";
import { Card, CardContent } from "@workspace/ui/components/card";
import { Button } from "@workspace/ui/components/button";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useTranslations } from "next-intl";
import { routes } from "@/lib/routes";
import { ArrowLeft, Bell, LetterUnread, Password, UserCircle } from "@solar-icons/react";
import { useHideBottomNavbar } from "@/hooks/use-hide-bottom-navbar";
import { useScrolled } from "@/hooks/use-scrolled";
import { NavRow } from "@/components/navigation/nav-row";
import { Separator } from "@workspace/ui/components/separator";

export default function ProfileSettingsLayout({ children }: { children: React.ReactNode }) {
    const pathname = usePathname();
    const t = useTranslations();
    const scrolled = useScrolled(100);
    useHideBottomNavbar();

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
        <div className="flex flex-col md:flex-row w-full justify-between h-fit md:h-[calc(100dvh-var(--header-height))] md:overflow-hidden">
            <div className="w-full md:w-1/3 md:p-8 md:overflow-y-auto">
                <div className="flex flex-col">
                    <div
                        className={cn(
                            "bg-card flex justify-between px-4 py-2 w-full fixed top-0 z-10 md:hidden",
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

                    {/* flex items-center justify-between w-full px-4 pt-13 md:py-2 md:pt-8 md:px-0 */}
                    <div className="pt-13 md:pt-0 px-4 md:px-0">
                        <h1 className="font-semibold tracking-tight text-2xl md:text-3xl">
                            {!isSettingsRoot && (
                                <span className="md:hidden">{currentPageLabel}</span>
                            )}
                            <span className={cn(!isSettingsRoot && "md:block hidden")}>
                                {t("ui.navigation.profileSettings")}
                            </span>
                        </h1>
                    </div>
                </div>

                <div className={cn("flex gap-6", !isSettingsRoot && "hidden md:block")}>
                    <div className="flex flex-col gap-2 p-4 py-2 md:py-4 md:px-0 w-full">
                        <Card className="p-0 ring-0">
                            <CardContent className="p-0 flex flex-col md:gap-1">
                                {settingsNav.map((item, index) => (
                                    <NavRow
                                        key={item.href}
                                        icon={item.icon}
                                        label={item.label}
                                        href={item.comingSoon ? "#" : item.href}
                                        destructive={false}
                                        displayArrow={!item.comingSoon}
                                        disabled={item.comingSoon}
                                        comingSoon={item.comingSoon}
                                        className={cn(
                                            "md:hover:bg-muted md:rounded-md md:p-4",
                                            (pathname.includes(item.href) ||
                                                (isSettingsRoot && index === 0)) &&
                                                "md:bg-muted",
                                        )}
                                    />
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <Separator orientation="vertical" className="hidden md:block w-[1px] h-full" />

            <div className="md:w-2/3 md:p-8 md:overflow-y-auto h-full">
                <div className="flex-1 md:py-6 md:pt-0">
                    <div
                        className={cn(
                            isSettingsRoot && "hidden md:block",
                            !isSettingsRoot && "p-4 md:p-0",
                        )}
                    >
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}

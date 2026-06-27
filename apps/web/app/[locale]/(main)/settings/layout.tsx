"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useTranslations } from "next-intl";
import {
    ArrowLeft,
    Bell,
    LetterUnread,
    Password,
    ShieldCheck,
    UserCircle,
    Card as CardIcon,
} from "@solar-icons/react";

import { Button } from "@workspace/ui/components/button";
import { Card, CardContent } from "@workspace/ui/components/card";
import { cn } from "@workspace/ui/lib/utils";

import { useNavigation } from "@/hooks/use-navigation";
import { NavRow } from "@/components/navigation/nav-row";
import { SplitPageLayout, SplitPageLayoutNavItem } from "@/components/layouts/split-page-layout";

import MyProfileAbout from "./about/page";

export default function ProfileSettingsLayout({ children }: { children: React.ReactNode }) {
    const pathname = usePathname();
    const t = useTranslations();
    const { routes } = useNavigation();

    const settingsNav: SplitPageLayoutNavItem[] = [
        {
            icon: UserCircle,
            label: t("ui.navigation.personalInformation"),
            href: routes.MyProfileAbout(),
            default: true,
        },
        {
            icon: Password,
            label: t("ui.navigation.changePassword"),
            href: routes.MyProfileChangePassword(),
            comingSoon: false,
        },
        {
            icon: ShieldCheck,
            label: t("ui.navigation.twoFactor"),
            href: routes.MyProfileTwoFactor(),
            comingSoon: false,
        },
        {
            icon: CardIcon,
            label: t("ui.navigation.paymentMethods"),
            href: routes.PaymentMethodsPage(),
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
        <SplitPageLayout isRoot={isSettingsRoot}>
            <SplitPageLayout.Sidebar>
                <SplitPageLayout.Header mobileOnly>
                    <Button variant="flat" size="icon-sm" asChild>
                        <Link href={isSettingsRoot ? routes.Profile() : routes.Settings()}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                </SplitPageLayout.Header>

                <div className="px-4 md:px-0">
                    <h1 className="font-semibold tracking-tight text-2xl md:text-3xl">
                        {!isSettingsRoot && <span className="md:hidden">{currentPageLabel}</span>}
                        <span className={cn(!isSettingsRoot && "md:block hidden")}>
                            {t("ui.navigation.profileSettings")}
                        </span>
                    </h1>
                </div>

                <SplitPageLayout.Nav>
                    <Card className="p-0 ring-0">
                        <CardContent className="p-0 flex flex-col md:gap-1">
                            {settingsNav.map((item) => (
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
                                            (isSettingsRoot && item.default)) &&
                                            "md:bg-muted",
                                    )}
                                />
                            ))}
                        </CardContent>
                    </Card>
                </SplitPageLayout.Nav>
            </SplitPageLayout.Sidebar>

            <SplitPageLayout.Content defaultContent={<MyProfileAbout />}>
                {children}
            </SplitPageLayout.Content>
        </SplitPageLayout>
    );
}

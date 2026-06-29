"use client";

import Link from "next/link";
import { useLocale, useTranslations } from "next-intl";
import {
    Bell,
    Logout2,
    QuestionCircle,
    Settings,
    ShieldCheck,
    UserCircle,
} from "@solar-icons/react";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent } from "@workspace/ui/components/card";
import { cn } from "@workspace/ui/lib/utils";
import { useAuth, EmailVerificationAlert } from "@/features/auth";
import { UserAvatar } from "@/features/auth/components/user-avatar";
import PageLayout from "@/components/layouts/page-layout";
import { useNavigation } from "@/hooks/use-navigation";
import { useIsMobile } from "@/hooks/use-mobile";
import Image from "next/image";
import { NavRow } from "@/components/navigation/nav-row";
import { CollaboratorInvitationsList } from "@/features/activities/components/collaborator-invitations-list";

function StatCard({ value, label }: { value: number | string; label: string }) {
    return (
        <div data-slot="stat-card" className="flex flex-col items-center gap-1 p-4">
            <span className="text-2xl font-bold text-primary">{value}</span>
            <span className="text-xs text-muted-foreground text-center leading-tight">{label}</span>
        </div>
    );
}

export default function ProfilePage() {
    const t = useTranslations();
    const { user, logout } = useAuth();
    const { routes } = useNavigation();
    const isMobile = useIsMobile();
    const locale = useLocale();

    const createdAtDate = user ? new Date(user.createdAt) : null;
    const isValidDate = createdAtDate !== null && !isNaN(createdAtDate.getTime());

    const yearsOnApp = isValidDate
        ? Math.max(0, new Date().getFullYear() - createdAtDate!.getFullYear())
        : 0;

    const memberSinceYear = isValidDate ? createdAtDate!.getFullYear().toString() : "0";

    const isManager = user?.hasAnyRoles(["manager"]) ?? false;

    return (
        <div className="container mx-auto md:px-4">
            <PageLayout
                Icon={UserCircle}
                title={t("features.profile.title")}
                headerTop={
                    <>
                        <Button
                            className="gap-2"
                            variant="flat"
                            size={isMobile ? "icon-sm" : "default"}
                        >
                            <Bell className="size-3.5" />
                            {!isMobile && t("ui.navigation.notifications")}
                        </Button>
                    </>
                }
            >
                <div className="flex flex-col gap-4">
                    <EmailVerificationAlert
                        title={t("features.auth.verifyAccount.title")}
                        description={t("features.auth.verifyAccount.description")}
                    />
                    {/* <div className="flex border border-border rounded-sm px-6 items-center justify-around gap-8">
                        <div className="flex flex-col items-center gap-3 pt-2">
                            <UserAvatar user={user} className="size-20 text-2xl" />
                            <div className="flex flex-col items-center gap-0.5">
                                <h2 className="text-xl font-bold">{user?.firstName}</h2>
                                <p className="text-sm text-center text-muted-foreground">
                                    {t("features.profile.memberSince", { date: memberSinceYear })}
                                </p>
                            </div>
                        </div>
                        <Card className="p-0 ring-0 rounded-xl">
                            <CardContent className="p-0">
                                <div className="grid grid-cols-1 divide-y">
                                    <StatCard value={0} label={t("features.profile.stats.bookings")} />
                                    <StatCard value={0} label={t("features.profile.stats.reviews")} />
                                    <StatCard
                                        value={yearsOnApp}
                                        label={t("features.profile.stats.yearsOnApp")}
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div> */}

                    <div className="flex flex-col items-center gap-3 pt-2">
                        <UserAvatar user={user} className="size-20 text-2xl" />
                        <div className="flex flex-col items-center gap-0.5">
                            <h2 className="text-xl font-bold">{user?.getFullName()}</h2>
                            <p className="text-sm text-muted-foreground">
                                {t("features.profile.memberSince", { date: memberSinceYear })}
                            </p>
                        </div>
                    </div>

                    <Card className="py-2">
                        <CardContent className="p-0">
                            <div className="grid grid-cols-3 divide-x">
                                <StatCard value={0} label={t("features.profile.stats.bookings")} />
                                <StatCard value={0} label={t("features.profile.stats.reviews")} />
                                <StatCard
                                    value={yearsOnApp}
                                    label={t("features.profile.stats.yearsOnApp")}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <CollaboratorInvitationsList />

                    {!isManager && (
                        <div
                            data-slot="host-verified-banner"
                            className="relative flex items-center gap-3 overflow-hidden rounded-3xl bg-secondary/20 p-4"
                        >
                            <span
                                aria-hidden
                                className="pointer-events-none absolute -top-5 -start-5 h-20 w-24 rounded-[50%] bg-secondary"
                            />
                            <div className="relative flex flex-1 flex-col gap-2">
                                <h3 className="text-lg font-semibold text-slate-900 whitespace-nowrap">
                                    {t("features.profile.becomeHost.title")}
                                </h3>
                                <p className="text-xs text-slate-700">
                                    {t("features.profile.becomeHost.description")}
                                </p>
                                <Button size="sm" className="w-fit" asChild>
                                    <Link href={routes.BecomeHost()}>
                                        {t("features.profile.becomeHost.cta")}
                                    </Link>
                                </Button>
                            </div>
                            <Image
                                src="/keny_illustration.png"
                                alt=""
                                aria-hidden
                                width={122}
                                height={92}
                                className="relative h-auto w-28 shrink-0 object-contain"
                            />
                        </div>
                    )}

                    <div className={cn("flex flex-col gap-2 mb-8", !isManager && "mb-0")}>
                        <Card className="p-0 ring-0">
                            <CardContent className="p-0">
                                <NavRow
                                    icon={Bell}
                                    label={t("ui.navigation.notifications")}
                                    href={`/${locale}/notifications`}
                                    displayArrow
                                />
                                <NavRow
                                    icon={Settings}
                                    label={t("ui.navigation.profileSettings")}
                                    href={routes.Settings()}
                                    displayArrow
                                />
                                <NavRow
                                    icon={QuestionCircle}
                                    label={t("features.profile.navigation.help")}
                                    href="#"
                                    displayArrow
                                    disabled
                                    comingSoon
                                />
                                <NavRow
                                    icon={ShieldCheck}
                                    label={t("features.profile.navigation.privacy")}
                                    href="#"
                                    displayArrow
                                    disabled
                                    comingSoon
                                />
                                <NavRow
                                    icon={Logout2}
                                    label={t("features.auth.logout")}
                                    onClick={logout}
                                    // destructive
                                />
                            </CardContent>
                        </Card>
                    </div>

                    {isManager && (
                        <Button
                            variant="default"
                            className="w-fit px-4 fixed bottom-14 left-1/2 transform -translate-x-1/2 mt-4"
                        >
                            {t("features.profile.switchToHostMode")}
                        </Button>
                    )}
                </div>
            </PageLayout>
        </div>
    );
}

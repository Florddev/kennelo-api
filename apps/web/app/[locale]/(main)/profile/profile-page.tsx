"use client";

import Link from "next/link";
import { useTranslations } from "next-intl";
import {
    AltArrowRight,
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
import { useAuth } from "@/features/auth";
import { UserAvatar } from "@/features/auth/components/user-avatar";
import PageLayout from "@/components/layouts/page-layout";
import { useNavigation } from "@/hooks/use-navigation";
import { useIsMobile } from "@/hooks/use-mobile";

function StatCard({ value, label }: { value: number | string; label: string }) {
    return (
        <div data-slot="stat-card" className="flex flex-col items-center gap-1 p-4">
            <span className="text-2xl font-bold text-primary">{value}</span>
            <span className="text-xs text-muted-foreground text-center leading-tight">{label}</span>
        </div>
    );
}

function NavRow({
    icon: Icon,
    label,
    href,
    onClick,
    destructive,
    displayArrow = false,
}: {
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    href?: string;
    onClick?: () => void;
    destructive?: boolean;
    displayArrow?: boolean;
}) {
    const className = cn(
        "flex items-center gap-3 py-3.5 px-0.5 w-full text-sm transition-colors",
        destructive ? "text-destructive" : "hover:text-primary",
    );

    const content = (
        <>
            <Icon className="size-4 shrink-0" />
            <span className="flex-1 text-start font-base">{label}</span>
            {displayArrow && <AltArrowRight className="size-4 text-muted-foreground shrink-0" />}
        </>
    );

    if (href) {
        return (
            <Link href={href} className={className}>
                {content}
            </Link>
        );
    }

    return (
        <button onClick={onClick} className={className}>
            {content}
        </button>
    );
}

export default function ProfilePage() {
    const t = useTranslations();
    const { user, logout } = useAuth();
    const { routes } = useNavigation();
    const isMobile = useIsMobile();

    const createdAtDate = user ? new Date(user.createdAt) : null;
    const isValidDate = createdAtDate !== null && !isNaN(createdAtDate.getTime());

    const yearsOnApp = isValidDate
        ? Math.max(0, new Date().getFullYear() - createdAtDate!.getFullYear())
        : 0;

    const memberSinceYear = isValidDate ? createdAtDate!.getFullYear().toString() : "";

    const isManager = user?.hasAnyRoles(["manager"]) ?? false;

    return (
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
                <div className="flex flex-col items-center gap-3 pt-2">
                    <UserAvatar user={user} className="size-20 text-2xl" />
                    <div className="flex flex-col items-center gap-0.5">
                        <h2 className="text-xl font-bold">{user?.getFullName()}</h2>
                        <p className="text-sm text-muted-foreground">
                            {t("features.profile.memberSince", { date: memberSinceYear })}
                        </p>
                    </div>
                </div>

                <Card>
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

                {!isManager && (
                    <Card className="border-primary/20 bg-gradient-to-br from-primary/5 to-secondary/10">
                        <CardContent className="p-4 flex flex-col gap-3">
                            <div className="flex flex-col gap-1">
                                <h3 className="font-semibold">
                                    {t("features.profile.becomeHost.title")}
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {t("features.profile.becomeHost.description")}
                                </p>
                            </div>
                            <Button size="sm" className="w-fit" asChild>
                                <Link href={routes.BecomeHost()}>
                                    {t("features.profile.becomeHost.cta")}
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                <div className="flex flex-col gap-2">
                    <Card className="p-0 ring-0">
                        <CardContent className="p-0">
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
                            />
                            <NavRow
                                icon={ShieldCheck}
                                label={t("features.profile.navigation.privacy")}
                                href="#"
                                displayArrow
                            />
                            <NavRow
                                icon={Logout2}
                                label={t("features.auth.logout")}
                                onClick={logout}
                                destructive
                            />
                        </CardContent>
                    </Card>
                </div>

                {isManager && (
                    <Button variant="secondary" className="w-full">
                        {t("features.profile.switchToHostMode")}
                    </Button>
                )}
            </div>
        </PageLayout>
    );
}

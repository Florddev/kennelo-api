"use client";

import Link from "next/link";
import { ChevronRight, HelpCircle, LogOut, Settings2, Shield } from "lucide-react";
import { useTranslations } from "next-intl";
import { UserCircle } from "@solar-icons/react";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent } from "@workspace/ui/components/card";
import { Separator } from "@workspace/ui/components/separator";
import { cn } from "@workspace/ui/lib/utils";
import { useAuth } from "@/features/auth";
import { UserAvatar } from "@/features/auth/components/user-avatar";
import PageLayout from "@/components/layouts/page-layout";
import { useNavigation } from "@/hooks/use-navigation";

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
}: {
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    href?: string;
    onClick?: () => void;
    destructive?: boolean;
}) {
    const className = cn(
        "flex items-center gap-3 py-3.5 w-full text-sm transition-colors",
        destructive ? "text-destructive" : "hover:text-primary",
    );

    const content = (
        <>
            <Icon className="size-4 shrink-0" />
            <span className="flex-1 text-start font-medium">{label}</span>
            <ChevronRight className="size-4 text-muted-foreground shrink-0" />
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

    const createdAtDate = user ? new Date(user.createdAt) : null;
    const isValidDate = createdAtDate !== null && !isNaN(createdAtDate.getTime());

    const yearsOnApp = isValidDate
        ? Math.max(0, new Date().getFullYear() - createdAtDate!.getFullYear())
        : 0;

    const memberSinceYear = isValidDate ? createdAtDate!.getFullYear().toString() : "";

    const isManager = user?.hasAnyRoles(["manager"]) ?? false;

    return (
        <PageLayout Icon={UserCircle} title={t("features.profile.title")}>
            <div className="flex flex-col gap-6">
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
                    <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider px-1">
                        {t("features.profile.account")}
                    </p>
                    <Card>
                        <CardContent className="px-4 py-0">
                            <NavRow
                                icon={Settings2}
                                label={t("features.profile.navigation.settings")}
                                href={routes.MyProfileAbout()}
                            />
                            <Separator />
                            <NavRow
                                icon={HelpCircle}
                                label={t("features.profile.navigation.help")}
                                href="#"
                            />
                            <Separator />
                            <NavRow
                                icon={Shield}
                                label={t("features.profile.navigation.privacy")}
                                href="#"
                            />
                            <Separator />
                            <NavRow
                                icon={LogOut}
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

"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useTranslations } from "next-intl";
import { ArrowLeft, Calendar, Card, Paw, UsersGroupTwoRounded, Widget5 } from "@solar-icons/react";

import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";

import { useNavigation } from "@/hooks/use-navigation";
import { NavRow } from "@/components/navigation/nav-row";
import { SplitPageLayout, SplitPageLayoutNavItem } from "@/components/layouts/split-page-layout";
import { useIsMobile } from "@/hooks/use-mobile";
import { useEstablishment } from "@/features/establishments";
import { Skeleton } from "@workspace/ui/components/skeleton";
import EstablishmentSettingsInformations from "./informations/page";

export default function EstablishmentLayout({ children }: { children: React.ReactNode }) {
    const pathname = usePathname();
    const t = useTranslations();
    const { routes, params } = useNavigation<{ id: string }>();
    const isMobile = useIsMobile();

    const settingsNav: SplitPageLayoutNavItem[] = [
        {
            icon: Widget5,
            href: routes.EstablishmentSettingsInformations({ id: params.id }),
            label: t("features.establishments.manager.nav.info"),
            default: true,
        },
        {
            icon: Calendar,
            href: routes.EstablishmentAvailabilities({ id: params.id }),
            label: t("features.establishments.manager.nav.availabilities"),
            default: false,
        },
        {
            icon: Paw,
            href: routes.EstablishmentCapacities({ id: params.id }),
            label: t("features.establishments.manager.nav.capacities"),
            default: false,
        },
        {
            href: routes.EstablishmentCollaborators({ id: params.id }),
            label: t("features.establishments.manager.nav.collaborators"),
            icon: UsersGroupTwoRounded,
            default: false,
        },
        {
            href: routes.EstablishmentPayment({ id: params.id }),
            label: t("features.establishments.manager.nav.payment"),
            icon: Card,
            default: false,
            comingSoon: true,
        },
    ];

    const isRoot = !settingsNav.some((item) => pathname.includes(item.href));
    const currentPageLabel = settingsNav.find((item) => pathname.includes(item.href))?.label;
    const { establishment } = useEstablishment(params.id);

    return (
        <SplitPageLayout isRoot={isRoot}>
            <SplitPageLayout.Sidebar className="md:w-1/4">
                <SplitPageLayout.Header>
                    <Button variant="flat" size={isMobile ? "icon-sm" : "default"} asChild>
                        <Link
                            href={
                                isRoot
                                    ? routes.EstablishmentDetails({ id: params.id })
                                    : routes.EstablishmentSettings({ id: params.id })
                            }
                        >
                            <ArrowLeft className="size-4" />
                            <span className="hidden md:block">
                                {t("common.actions.backTo", { value: establishment?.name ?? "" })}
                            </span>
                        </Link>
                    </Button>
                </SplitPageLayout.Header>

                <div className="px-4 md:px-0">
                    {establishment ? (
                        <h1 className="font-semibold tracking-tight text-2xl md:text-3xl">
                            {!isRoot && <span className="md:hidden">{currentPageLabel}</span>}
                            <span className={cn(!isRoot && "md:block hidden")}>
                                {t("ui.navigation.settings")}
                            </span>
                        </h1>
                    ) : (
                        <Skeleton className="h-8 w-3/4" />
                    )}
                </div>

                <SplitPageLayout.Nav>
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
                                (pathname.includes(item.href) || (isRoot && item.default)) &&
                                    "md:bg-muted",
                            )}
                        />
                    ))}
                </SplitPageLayout.Nav>
            </SplitPageLayout.Sidebar>

            <SplitPageLayout.Content
                className="md:w-3/4"
                defaultContent={<EstablishmentSettingsInformations />}
            >
                {children}
            </SplitPageLayout.Content>
        </SplitPageLayout>
    );
}

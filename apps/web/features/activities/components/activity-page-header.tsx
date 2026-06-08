"use client";

import React from "react";
import { usePathname } from "next/navigation";
import { useTranslations } from "next-intl";

import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from "@workspace/ui/components/breadcrumb";

import { useNavigation } from "@/hooks/use-navigation";
import { useActivity } from "../hooks/use-activity";

export function ActivityPageHeader({ children }: { children?: React.ReactNode }) {
    const t = useTranslations();
    const pathname = usePathname();
    const { routes, params } = useNavigation<{ id: string }>();
    const { activity } = useActivity(params.id);
    const id = params.id;

    const marker = `/host/${id}/`;
    const markerIdx = pathname.indexOf(marker);
    const pathAfterBase = markerIdx !== -1 ? pathname.slice(markerIdx + marker.length) : "";
    const segments = pathAfterBase ? pathAfterBase.split("/") : [];

    const labelMap: Record<string, string> = {
        overview: t("ui.navigation.overview"),
        bookings: t("features.activities.manager.nav.bookings"),
        invoices: t("features.activities.manager.nav.invoices"),
        settings: t("ui.navigation.settings"),
        informations: t("features.activities.manager.nav.info"),
        availabilities: t("features.activities.manager.nav.availabilities"),
        cycles: t("features.activities.manager.nav.cycles"),
        collaborators: t("features.activities.manager.nav.collaborators"),
        payment: t("features.activities.manager.nav.payment"),
    };

    const hrefMap: Record<string, string> = {
        overview: routes.ActivityOverview({ id }),
        bookings: routes.ActivityBookings({ id }),
        invoices: routes.ActivityInvoices({ id }),
        settings: routes.ActivitySettings({ id }),
        "settings/informations": routes.ActivitySettingsInformations({ id }),
        "settings/availabilities": routes.ActivityAvailabilities({ id }),
        "settings/cycles": routes.ActivityCycles({ id }),
        "settings/collaborators": routes.ActivityCollaborators({ id }),
        "settings/payment": routes.ActivityPayment({ id }),
    };

    return (
        <div
            data-slot="activity-page-header"
            className="pt-4 md:pt-0 md:min-h-8 w-full flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
        >
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink href={routes.ActivityDetails({ id })}>
                            {activity?.name}
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    {segments.map((segment, index) => {
                        const accumulatedPath = segments.slice(0, index + 1).join("/");
                        const isLast = index === segments.length - 1;

                        return (
                            <React.Fragment key={accumulatedPath}>
                                <BreadcrumbSeparator />
                                <BreadcrumbItem>
                                    {isLast ? (
                                        <BreadcrumbPage>
                                            {labelMap[segment] ?? segment}
                                        </BreadcrumbPage>
                                    ) : (
                                        <BreadcrumbLink href={hrefMap[accumulatedPath] ?? "#"}>
                                            {labelMap[segment] ?? segment}
                                        </BreadcrumbLink>
                                    )}
                                </BreadcrumbItem>
                            </React.Fragment>
                        );
                    })}
                </BreadcrumbList>
            </Breadcrumb>
            {children && <div className="flex gap-2">{children}</div>}
        </div>
    );
}

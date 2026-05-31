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
import { useEstablishment } from "../hooks/use-establishment";

export function EstablishmentPageHeader({ children }: { children?: React.ReactNode }) {
    const t = useTranslations();
    const pathname = usePathname();
    const { routes, params } = useNavigation<{ id: string }>();
    const { establishment } = useEstablishment(params.id);
    const id = params.id;

    const marker = `/host/${id}/`;
    const markerIdx = pathname.indexOf(marker);
    const pathAfterBase = markerIdx !== -1 ? pathname.slice(markerIdx + marker.length) : "";
    const segments = pathAfterBase ? pathAfterBase.split("/") : [];

    const labelMap: Record<string, string> = {
        overview: t("ui.navigation.overview"),
        bookings: t("features.establishments.manager.nav.bookings"),
        invoices: t("features.establishments.manager.nav.invoices"),
        settings: t("ui.navigation.settings"),
        informations: t("features.establishments.manager.nav.info"),
        availabilities: t("features.establishments.manager.nav.availabilities"),
        capacities: t("features.establishments.manager.nav.capacities"),
        collaborators: t("features.establishments.manager.nav.collaborators"),
        payment: t("features.establishments.manager.nav.payment"),
    };

    const hrefMap: Record<string, string> = {
        overview: routes.EstablishmentOverview({ id }),
        bookings: routes.EstablishmentBookings({ id }),
        invoices: routes.EstablishmentInvoices({ id }),
        settings: routes.EstablishmentSettings({ id }),
        "settings/informations": routes.EstablishmentSettingsInformations({ id }),
        "settings/availabilities": routes.EstablishmentAvailabilities({ id }),
        "settings/capacities": routes.EstablishmentCapacities({ id }),
        "settings/collaborators": routes.EstablishmentCollaborators({ id }),
        "settings/payment": routes.EstablishmentPayment({ id }),
    };

    return (
        <div
            data-slot="establishment-page-header"
            className="h-8 w-full flex items-center justify-between"
        >
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink href={routes.EstablishmentDetails({ id })}>
                            {establishment?.name}
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

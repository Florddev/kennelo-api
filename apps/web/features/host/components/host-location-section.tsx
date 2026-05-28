import { useTranslations } from "next-intl";
import { MapPin } from "lucide-react";
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from "@workspace/ui/components/empty";
import type { AddressModel } from "@workspace/modules/address";

import { HostLocationMap } from "./host-location-map";

type HostLocationSectionProps = {
    address: AddressModel | null;
};

export function HostLocationSection({ address }: HostLocationSectionProps) {
    const t = useTranslations();

    if (!address) {
        return (
            <section className="flex flex-col gap-3">
                <h2 className="text-lg font-semibold text-slate-900">
                    {t("features.host.detail.location")}
                </h2>
                <Empty className="rounded-2xl border py-8">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <MapPin />
                        </EmptyMedia>
                        <EmptyTitle>{t("features.host.detail.locationEmpty")}</EmptyTitle>
                    </EmptyHeader>
                </Empty>
            </section>
        );
    }

    const hasCoords = address.latitude !== null && address.longitude !== null;

    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.host.detail.location")}
            </h2>
            <p className="text-sm text-foreground">
                {address.city}, {address.country}
            </p>
            {hasCoords ? (
                <HostLocationMap
                    latitude={address.latitude as number}
                    longitude={address.longitude as number}
                    label={address.city}
                />
            ) : (
                <Empty className="rounded-2xl border py-8">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <MapPin />
                        </EmptyMedia>
                        <EmptyTitle>{t("features.host.detail.locationEmpty")}</EmptyTitle>
                    </EmptyHeader>
                </Empty>
            )}
        </section>
    );
}

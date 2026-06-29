"use client";

import { useTranslations } from "next-intl";
import { MapPin } from "lucide-react";
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from "@workspace/ui/components/empty";
import { Map, MapMarker, MarkerContent } from "@workspace/ui/components/mapcn";
import type { AddressModel } from "@workspace/modules/address";
import { MapArrowSquare, MapPoint } from "@solar-icons/react";

type HostLocationSectionProps = {
    address: AddressModel | null;
};

function LocationMarker() {
    return <MapPoint className="size-8 text-primary" weight="Bold" />;
}

function LocationMap({
    latitude,
    longitude,
    city,
}: {
    latitude: number;
    longitude: number;
    city: string;
}) {
    const t = useTranslations();
    const directionsUrl = `https://www.google.com/maps/search/?api=1&query=${latitude},${longitude}`;

    return (
        <div className="relative aspect-[660/400] w-full overflow-hidden rounded-3xl bg-muted">
            <Map center={[longitude, latitude]} zoom={14} className="absolute inset-0 rounded-none">
                {/* <MapControls position="bottom-right" showZoom /> */}
                <MapMarker longitude={longitude} latitude={latitude}>
                    <MarkerContent>
                        <LocationMarker />
                    </MarkerContent>
                </MapMarker>
            </Map>
            <a
                href={directionsUrl}
                target="_blank"
                rel="noopener noreferrer"
                aria-label={t("features.host.detail.directions")}
                className="absolute top-2 end-2 z-10 inline-flex items-center gap-1.5 rounded-full bg-background/95 px-3 py-1.5 text-xs font-medium shadow-md hover:bg-background"
            >
                <MapArrowSquare className="size-3.5 text-primary" weight="Bold" />
                {t("features.host.detail.directions")}
                <span className="sr-only">{city}</span>
            </a>
        </div>
    );
}

export function HostLocationSection({ address }: HostLocationSectionProps) {
    const t = useTranslations();

    if (!address) {
        return (
            <section data-slot="host-location-section" className="flex flex-col gap-3">
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

    const hasCoordinates = address.latitude !== null && address.longitude !== null;

    return (
        <section data-slot="host-location-section" className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.host.detail.location")}
            </h2>
            <p className="text-sm text-foreground">
                {address.city}, {address.country}
            </p>
            {hasCoordinates ? (
                <LocationMap
                    latitude={address.latitude as number}
                    longitude={address.longitude as number}
                    city={address.city}
                />
            ) : (
                <div className="relative aspect-[660/400] w-full overflow-hidden rounded-3xl bg-muted">
                    <div className="absolute inset-0 flex items-center justify-center">
                        <div className="flex items-center gap-1.5 rounded-full bg-background/95 px-3 py-1.5 text-xs font-medium shadow-md">
                            <MapPin className="size-3.5 text-primary" />
                            {address.city}
                        </div>
                    </div>
                </div>
            )}
        </section>
    );
}

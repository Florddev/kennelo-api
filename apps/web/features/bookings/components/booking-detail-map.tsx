"use client";

import { useTranslations } from "next-intl";
import { MapPoint } from "@solar-icons/react";
import { MapPin } from "lucide-react";

import type { AddressModel } from "@workspace/modules/address";
import { Map, MapControls, MapMarker, MarkerContent } from "@workspace/ui/components/mapcn";

const DETAIL_ZOOM = 15;

export function BookingDetailMap({ address }: { address: AddressModel | null }) {
    const t = useTranslations();

    if (!address || address.latitude === null || address.longitude === null) {
        return (
            <div className="w-full h-full flex flex-col items-center justify-center gap-3 bg-muted text-muted-foreground">
                <MapPoint className="size-8" />
                <span className="text-sm">{t("features.bookings.detail.noAddress")}</span>
            </div>
        );
    }

    return (
        <Map center={[address.longitude, address.latitude]} zoom={DETAIL_ZOOM}>
            <MapMarker longitude={address.longitude} latitude={address.latitude}>
                <MarkerContent>
                    <MapPin className="size-8 fill-primary/30 text-primary" />
                </MarkerContent>
            </MapMarker>
            <MapControls position="bottom-right" />
        </Map>
    );
}

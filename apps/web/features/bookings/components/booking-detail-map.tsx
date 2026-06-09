"use client";

import { useTranslations } from "next-intl";
import { MapPoint } from "@solar-icons/react";

import type { AddressModel } from "@workspace/modules/address";

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

    const lat = address.latitude;
    const lon = address.longitude;
    const delta = 0.008;
    const src = `https://www.openstreetmap.org/export/embed.html?bbox=${lon - delta},${lat - delta},${lon + delta},${lat + delta}&layer=mapnik&marker=${lat},${lon}`;

    return (
        <iframe
            src={src}
            title={t("features.bookings.detail.locationSection")}
            className="w-full h-full border-0"
            loading="lazy"
            referrerPolicy="no-referrer-when-downgrade"
        />
    );
}

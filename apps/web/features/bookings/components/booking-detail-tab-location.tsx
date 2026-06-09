"use client";

import { useTranslations } from "next-intl";
import { MapPoint, Copy, Route } from "@solar-icons/react";
import { toast } from "sonner";

import type { AddressModel } from "@workspace/modules/address";

import { NavRow } from "@/components/navigation/nav-row";
import { BookingMiniMap } from "./booking-mini-map";

export function BookingDetailTabLocation({ address }: { address: AddressModel | null }) {
    const t = useTranslations();

    const handleCopyAddress = () => {
        if (!address) return;
        const full = address.getFullAddress();
        if (navigator?.clipboard) {
            navigator.clipboard
                .writeText(full)
                .then(() => toast.success(t("features.bookings.detail.addressCopied")))
                .catch(() => {});
        }
    };

    const handleGetDirections = () => {
        if (!address?.latitude || !address?.longitude) return;
        window.open(
            `https://www.openstreetmap.org/directions?to=${address.latitude},${address.longitude}`,
            "_blank",
            "noopener,noreferrer",
        );
    };

    if (!address) {
        return (
            <div className="p-4 pb-10">
                <p className="text-sm text-muted-foreground">
                    {t("features.bookings.detail.noAddress")}
                </p>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-4 p-4 pb-10">
            <BookingMiniMap address={address} className="md:hidden" />

            <div className="flex items-start gap-3">
                <MapPoint className="size-4 text-muted-foreground mt-0.5 shrink-0" />
                <span className="text-sm text-muted-foreground">{address.getFullAddress()}</span>
            </div>

            <div className="flex flex-col">
                <NavRow
                    icon={Copy}
                    label={t("features.bookings.detail.copyAddress")}
                    onClick={handleCopyAddress}
                    displayArrow
                />
                <NavRow
                    icon={Route}
                    label={t("features.bookings.detail.getDirections")}
                    onClick={handleGetDirections}
                    displayArrow
                />
            </div>
        </div>
    );
}

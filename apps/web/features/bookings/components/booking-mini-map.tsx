"use client";

import { MapPin } from "lucide-react";

import type { AddressModel } from "@workspace/modules/address";
import { Map, MapMarker, MarkerContent } from "@workspace/ui/components/mapcn";
import { cn } from "@workspace/ui/lib/utils";

const MINI_ZOOM = 14;

export function BookingMiniMap({
    address,
    className,
}: {
    address: AddressModel;
    className?: string;
}) {
    if (address.latitude === null || address.longitude === null) return null;

    return (
        <div className={cn("aspect-[16/7] rounded-2xl overflow-hidden", className)}>
            <Map
                center={[address.longitude, address.latitude]}
                zoom={MINI_ZOOM}
                interactive={false}
            >
                <MapMarker longitude={address.longitude} latitude={address.latitude}>
                    <MarkerContent>
                        <MapPin className="size-7 fill-primary/30 text-primary" />
                    </MarkerContent>
                </MapMarker>
            </Map>
        </div>
    );
}

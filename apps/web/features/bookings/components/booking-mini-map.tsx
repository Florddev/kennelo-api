"use client";

import type { AddressModel } from "@workspace/modules/address";
import { cn } from "@workspace/ui/lib/utils";

export function BookingMiniMap({
    address,
    className,
}: {
    address: AddressModel;
    className?: string;
}) {
    if (address.latitude === null || address.longitude === null) return null;

    const lat = address.latitude;
    const lon = address.longitude;
    const delta = 0.005;
    const src = `https://www.openstreetmap.org/export/embed.html?bbox=${lon - delta},${lat - delta},${lon + delta},${lat + delta}&layer=mapnik&marker=${lat},${lon}`;

    return (
        <div className={cn("aspect-[16/7] rounded-2xl overflow-hidden", className)}>
            <iframe
                src={src}
                className="w-full h-full border-0"
                loading="lazy"
                referrerPolicy="no-referrer-when-downgrade"
                title="minimap"
            />
        </div>
    );
}

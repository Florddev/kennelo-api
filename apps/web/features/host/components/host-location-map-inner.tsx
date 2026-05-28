"use client";

import { useEffect, useRef } from "react";
import "leaflet/dist/leaflet.css";

type HostLocationMapInnerProps = {
    latitude: number;
    longitude: number;
    label: string;
};

export default function HostLocationMapInner({
    latitude,
    longitude,
    label,
}: HostLocationMapInnerProps) {
    const containerRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        let cancelled = false;
        let map: import("leaflet").Map | null = null;

        (async () => {
            const L = (await import("leaflet")).default;
            if (cancelled || !containerRef.current) return;

            map = L.map(containerRef.current, {
                scrollWheelZoom: false,
                zoomControl: true,
            }).setView([latitude, longitude], 13);

            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution:
                    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(map);

            const pinIcon = L.divIcon({
                className: "host-location-pin",
                html: `<div style="background:#fff;border-radius:9999px;padding:6px 10px;font-size:12px;font-weight:500;box-shadow:0 4px 10px rgba(0,0,0,0.15);white-space:nowrap;">${label}</div>`,
                iconAnchor: [40, 16],
            });

            L.marker([latitude, longitude], { icon: pinIcon }).addTo(map);
        })();

        return () => {
            cancelled = true;
            if (map) {
                map.remove();
                map = null;
            }
        };
    }, [latitude, longitude, label]);

    return (
        <div
            ref={containerRef}
            data-slot="host-location-map"
            className="aspect-[660/400] w-full overflow-hidden rounded-3xl bg-muted"
        />
    );
}

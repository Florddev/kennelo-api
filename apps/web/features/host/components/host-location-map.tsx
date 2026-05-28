"use client";

import dynamic from "next/dynamic";

const HostLocationMapInner = dynamic(() => import("./host-location-map-inner"), {
    ssr: false,
    loading: () => (
        <div className="aspect-[660/400] w-full animate-pulse overflow-hidden rounded-3xl bg-muted" />
    ),
});

type HostLocationMapProps = {
    latitude: number;
    longitude: number;
    label: string;
};

export function HostLocationMap({ latitude, longitude, label }: HostLocationMapProps) {
    return <HostLocationMapInner latitude={latitude} longitude={longitude} label={label} />;
}

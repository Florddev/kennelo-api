"use client";

import { useCallback } from "react";
import { usePlatform } from "@/hooks/use-platform";

export type GeolocationCoords = {
    lat: number;
    lng: number;
};

export type GeolocationResult =
    | { coords: GeolocationCoords; error: null }
    | { coords: null; error: "denied" | "unavailable" };

export function useGeolocation() {
    const { isNative } = usePlatform();

    const requestPosition = useCallback(async (): Promise<GeolocationResult> => {
        if (isNative) {
            try {
                const { Geolocation } = await import("@capacitor/geolocation");
                await Geolocation.requestPermissions();
                const position = await Geolocation.getCurrentPosition({
                    enableHighAccuracy: false,
                    timeout: 10000,
                });
                return {
                    coords: {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    },
                    error: null,
                };
            } catch {
                return { coords: null, error: "denied" };
            }
        }

        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve({ coords: null, error: "unavailable" });
                return;
            }

            // eslint-disable-next-line sonarjs/no-intrusive-permissions
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        coords: {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        },
                        error: null,
                    });
                },
                (err) => {
                    resolve({
                        coords: null,
                        error: err.code === err.PERMISSION_DENIED ? "denied" : "unavailable",
                    });
                },
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 },
            );
        });
    }, [isNative]);

    return { requestPosition };
}

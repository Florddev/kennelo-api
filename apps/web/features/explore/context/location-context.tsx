"use client";

import { createContext, useContext, useState, useCallback, type ReactNode } from "react";
import type { GeolocationCoords } from "@/hooks/use-geolocation";

type LocationError = "denied" | "unavailable" | null;

type LocationContextValue = {
    coords: GeolocationCoords | null;
    error: LocationError;
    isDismissed: boolean;
    setCoords: (coords: GeolocationCoords) => void;
    setError: (error: LocationError) => void;
    dismiss: () => void;
};

const LocationContext = createContext<LocationContextValue | undefined>(undefined);

export function LocationProvider({ children }: { children: ReactNode }) {
    const [coords, setCoords] = useState<GeolocationCoords | null>(null);
    const [error, setError] = useState<LocationError>(null);
    const [isDismissed, setIsDismissed] = useState(false);

    const dismiss = useCallback(() => setIsDismissed(true), []);

    return (
        <LocationContext.Provider
            value={{ coords, error, isDismissed, setCoords, setError, dismiss }}
        >
            {children}
        </LocationContext.Provider>
    );
}

export function useLocation() {
    const context = useContext(LocationContext);
    if (!context) throw new Error("useLocation must be used within a LocationProvider");
    return context;
}

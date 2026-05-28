"use client";

import { useState, useEffect } from "react";
import { searchEstablishments } from "@workspace/modules/establishments";
import type { EstablishmentModel } from "@workspace/modules/establishments";

type UseSearchResultsInput = {
    location?: string;
    coords?: { lat: number; lng: number };
    radius?: number;
    dateFrom?: string;
    dateTo?: string;
    animalCounts?: Record<string, number>;
};

export function useSearchResults({
    location,
    coords,
    radius,
    dateFrom,
    dateTo,
    animalCounts,
}: UseSearchResultsInput) {
    const [establishments, setEstablishments] = useState<EstablishmentModel[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [retryKey, setRetryKey] = useState(0);

    useEffect(() => {
        let cancelled = false;
        const loadingTimer = setTimeout(() => {
            if (cancelled) return;
            setIsLoading(true);
            setError(null);
        }, 0);
        searchEstablishments({ location, coords, radius, dateFrom, dateTo, animalCounts })
            .then((result) => {
                if (!cancelled) setEstablishments(result.establishments);
            })
            .catch(() => {
                if (!cancelled) setError("error");
            })
            .finally(() => {
                if (!cancelled) setIsLoading(false);
            });
        return () => {
            cancelled = true;
            clearTimeout(loadingTimer);
        };
    }, [location, coords?.lat, coords?.lng, radius, dateFrom, dateTo, animalCounts, retryKey]);

    return { establishments, isLoading, error, retry: () => setRetryKey((k) => k + 1) };
}

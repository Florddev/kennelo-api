"use client";

import { useState, useEffect } from "react";
import { searchActivities } from "@workspace/modules/activities";
import type { ActivityModel } from "@workspace/modules/activities";

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
    const [activities, setActivities] = useState<ActivityModel[]>([]);
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
        searchActivities({ location, coords, radius, dateFrom, dateTo, animalCounts })
            .then((result) => {
                if (!cancelled) setActivities(result.activities);
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
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [location, coords?.lat, coords?.lng, radius, dateFrom, dateTo, animalCounts, retryKey]);

    return { activities, isLoading, error, retry: () => setRetryKey((k) => k + 1) };
}

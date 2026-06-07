"use client";

import { useState, useEffect, useCallback } from "react";
import { getExploreActivities } from "@workspace/modules/activities";
import type { ExploreSectionModel } from "@workspace/modules/activities";
import { useAsyncState } from "@/hooks/use-async-state";
import { useLocation } from "@/features/explore/context/location-context";

export function useExploreActivities() {
    const { execute, isLoading, error } = useAsyncState();
    const { coords } = useLocation();
    const [sections, setSections] = useState<ExploreSectionModel[]>([]);

    const load = useCallback(() => {
        execute(() => getExploreActivities(coords ?? undefined), {
            onSuccess: setSections,
            displayError: false,
        });
    }, [coords, execute]);

    useEffect(() => {
        load();
    }, [load]);

    return { sections, isLoading, error, retry: load };
}

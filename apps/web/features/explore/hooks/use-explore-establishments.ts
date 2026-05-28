"use client";

import { useState, useEffect, useCallback } from "react";
import { getExploreEstablishments } from "@workspace/modules/establishments";
import type { ExploreSectionModel } from "@workspace/modules/establishments";
import { useAsyncState } from "@/hooks/use-async-state";
import { useLocation } from "@/features/explore/context/location-context";

export function useExploreEstablishments() {
    const { execute, isLoading, error } = useAsyncState();
    const { coords } = useLocation();
    const [sections, setSections] = useState<ExploreSectionModel[]>([]);

    const load = useCallback(() => {
        execute(() => getExploreEstablishments(coords ?? undefined), {
            onSuccess: setSections,
            displayError: false,
        });
    }, [coords, execute]);

    useEffect(() => {
        load();
    }, [load]);

    return { sections, isLoading, error, retry: load };
}

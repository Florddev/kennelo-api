"use client";

import { useQuery } from "@tanstack/react-query";
import { getExploreEstablishments } from "@workspace/modules/establishments";

export function useExploreEstablishments() {
    const { data, isLoading, error } = useQuery({
        queryKey: ["explore", "establishments"],
        queryFn: getExploreEstablishments,
    });

    return { establishments: data ?? [], isLoading, error };
}

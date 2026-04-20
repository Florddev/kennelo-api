"use client";

import { useQuery } from "@tanstack/react-query";
import { getEstablishments } from "@workspace/modules/establishments";

export function useExploreEstablishments() {
    const { data, isLoading, error } = useQuery({
        queryKey: ["explore", "establishments"],
        queryFn: getEstablishments,
    });

    return { establishments: data ?? [], isLoading, error };
}

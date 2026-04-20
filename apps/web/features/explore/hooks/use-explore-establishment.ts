"use client";

import { useQuery } from "@tanstack/react-query";
import { getEstablishment, getCapacities } from "@workspace/modules/establishments";

export function useExploreEstablishment(id: string) {
    const establishmentQuery = useQuery({
        queryKey: ["explore", "establishment", id],
        queryFn: () => getEstablishment(id),
        enabled: Boolean(id),
    });

    const capacitiesQuery = useQuery({
        queryKey: ["explore", "establishment", id, "capacities"],
        queryFn: () => getCapacities(id),
        enabled: Boolean(id),
    });

    return {
        establishment: establishmentQuery.data ?? null,
        capacities: capacitiesQuery.data ?? [],
        isLoading: establishmentQuery.isLoading || capacitiesQuery.isLoading,
        error: establishmentQuery.error ?? capacitiesQuery.error,
    };
}

"use client";

import { useQuery } from "@tanstack/react-query";
import { getEstablishment, getCapacities } from "@workspace/modules/establishments";

export function useHostEstablishment(id: string) {
    const establishmentQuery = useQuery({
        queryKey: ["host", "establishment", id],
        queryFn: () => getEstablishment(id),
        enabled: Boolean(id),
    });

    const capacitiesQuery = useQuery({
        queryKey: ["host", "establishment", id, "capacities"],
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

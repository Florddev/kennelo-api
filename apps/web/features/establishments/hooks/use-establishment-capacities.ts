"use client";

import { useQuery } from "@tanstack/react-query";

import { getCapacities, type CapacityModel } from "@workspace/modules/establishments";

type UseEstablishmentCapacitiesResult = {
    capacities: CapacityModel[];
    isLoading: boolean;
    isError: boolean;
};

export function useEstablishmentCapacities(
    establishmentId: string,
): UseEstablishmentCapacitiesResult {
    const { data, isLoading, isError } = useQuery({
        queryKey: ["establishment-capacities", establishmentId],
        queryFn: () => getCapacities(establishmentId),
        staleTime: 60_000,
    });

    return {
        capacities: data ?? [],
        isLoading,
        isError,
    };
}

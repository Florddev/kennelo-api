"use client";

import { useQuery } from "@tanstack/react-query";

import { getEstablishment, type EstablishmentModel } from "@workspace/modules/establishments";

type UseEstablishmentResult = {
    establishment: EstablishmentModel | null;
    isLoading: boolean;
    isError: boolean;
};

export function establishmentQueryKey(id: string): [string, string] {
    return ["establishment", id];
}

export function useEstablishment(id: string): UseEstablishmentResult {
    const { data, isLoading, isError } = useQuery({
        queryKey: establishmentQueryKey(id),
        queryFn: () => getEstablishment(id),
        staleTime: 60_000,
        enabled: Boolean(id),
    });

    return {
        establishment: data ?? null,
        isLoading,
        isError,
    };
}

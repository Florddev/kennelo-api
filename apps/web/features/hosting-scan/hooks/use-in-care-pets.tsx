"use client";

import { useQuery } from "@tanstack/react-query";

import { getInCarePets } from "@workspace/modules/scanners";

export function useInCarePets(enabled: boolean) {
    const query = useQuery({
        queryKey: ["in-care-pets"],
        queryFn: getInCarePets,
        enabled,
        staleTime: 60 * 1000,
    });

    return {
        pets: query.data ?? [],
        isLoading: query.isLoading,
        isError: query.isError,
        refetch: query.refetch,
    };
}

"use client";

import { useQuery } from "@tanstack/react-query";

import { getHostScanLookup } from "@workspace/modules/scanners";

export function useHostScanLookup(microchipNumber: string) {
    const query = useQuery({
        queryKey: ["host-scan-lookup", microchipNumber],
        queryFn: () => getHostScanLookup(microchipNumber),
        enabled: microchipNumber.length > 0,
    });

    return {
        lookup: query.data ?? null,
        isLoading: query.isLoading,
        isError: query.isError,
        refetch: query.refetch,
    };
}

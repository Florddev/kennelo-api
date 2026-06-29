"use client";

import { useQuery } from "@tanstack/react-query";

import { getScannerScans } from "@workspace/modules/scanners";

export function useScannerScans() {
    const query = useQuery({
        queryKey: ["scanner-scans"],
        queryFn: getScannerScans,
        staleTime: 60 * 1000,
    });

    return {
        scans: query.data ?? [],
        isLoading: query.isLoading,
        isError: query.isError,
        refetch: query.refetch,
    };
}

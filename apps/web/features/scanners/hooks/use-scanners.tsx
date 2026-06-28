"use client";

import { useQuery, useQueryClient } from "@tanstack/react-query";
import { getScanners, deleteScanner, ScannerModel } from "@workspace/modules/scanners";

export const SCANNERS_QUERY_KEY = ["scanners", "list"];

export function useScanners() {
    const queryClient = useQueryClient();
    const { data, isLoading, error } = useQuery({
        queryKey: SCANNERS_QUERY_KEY,
        queryFn: getScanners,
    });

    const remove = async (scanner: ScannerModel) => {
        await deleteScanner(scanner.id);
        queryClient.setQueryData<ScannerModel[]>(SCANNERS_QUERY_KEY, (prev) =>
            prev ? prev.filter((s) => s.id !== scanner.id) : [],
        );
    };

    const invalidate = () => queryClient.invalidateQueries({ queryKey: SCANNERS_QUERY_KEY });

    return { scanners: data ?? [], isLoading, error, remove, invalidate };
}

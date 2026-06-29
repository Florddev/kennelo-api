"use client";

import { useQuery } from "@tanstack/react-query";
import { getPets } from "@workspace/modules/pets";

export function usePets(options?: { enabled?: boolean }) {
    const { data, isLoading, error } = useQuery({
        queryKey: ["pets", "list"],
        queryFn: getPets,
        enabled: options?.enabled ?? true,
    });

    return { pets: data ?? [], isLoading, error };
}

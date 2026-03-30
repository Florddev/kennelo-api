"use client";

import { useQuery } from "@tanstack/react-query";
import { getPets } from "@workspace/modules/pets";

export function usePets() {
    const { data, isLoading, error } = useQuery({
        queryKey: ["pets", "list"],
        queryFn: getPets,
    });

    return { pets: data ?? [], isLoading, error };
}

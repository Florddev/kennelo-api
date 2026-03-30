"use client";

import { useQuery } from "@tanstack/react-query";
import { getPet } from "@workspace/modules/pets";

export function usePet(id: string) {
    const { data, isLoading, error } = useQuery({
        queryKey: ["pets", "detail", id],
        queryFn: () => getPet(id),
        enabled: Boolean(id),
    });

    return { pet: data ?? null, isLoading, error };
}

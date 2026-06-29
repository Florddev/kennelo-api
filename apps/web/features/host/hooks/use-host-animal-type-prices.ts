"use client";

import { useQuery } from "@tanstack/react-query";
import { getActivityAnimalTypePrices } from "@workspace/modules/activities";

export function useHostAnimalTypePrices(activityId: string) {
    const { data, isLoading } = useQuery({
        queryKey: ["host", "animal-type-prices", activityId],
        queryFn: () => getActivityAnimalTypePrices(activityId),
        enabled: Boolean(activityId),
    });

    return {
        priceRanges: data ?? [],
        isLoading,
    };
}

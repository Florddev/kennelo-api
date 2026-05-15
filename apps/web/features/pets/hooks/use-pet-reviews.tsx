"use client";

import { useQuery } from "@tanstack/react-query";
import { getPetReviews, PetReviewModel } from "@workspace/modules/pets";

export function usePetReviews(petId: string) {
    const { data, isLoading, error } = useQuery<PetReviewModel[]>({
        queryKey: ["pets", "reviews", petId],
        queryFn: () => getPetReviews(petId),
        enabled: Boolean(petId),
    });

    return { reviews: data ?? [], isLoading, error };
}

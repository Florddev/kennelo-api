"use client";

import { useQuery } from "@tanstack/react-query";
import { getActivityReviews } from "@workspace/modules/reviews";

const REVIEWS_PER_PAGE = 50;

export function useHostReviews(activityId: string) {
    const { data, isLoading } = useQuery({
        queryKey: ["host", "reviews", activityId],
        queryFn: () => getActivityReviews(activityId, { perPage: REVIEWS_PER_PAGE }),
        enabled: Boolean(activityId),
    });

    const reviews = data ?? [];
    const reviewCount = reviews.length;
    const averageRating =
        reviewCount > 0
            ? reviews.reduce((sum, review) => sum + review.overallRating, 0) / reviewCount
            : null;

    return { reviews, reviewCount, averageRating, isLoading };
}

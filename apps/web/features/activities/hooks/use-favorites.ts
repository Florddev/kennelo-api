"use client";

import { useInfiniteQuery } from "@tanstack/react-query";
import { getFavorites } from "@workspace/modules/activities";

export function useFavorites() {
    const query = useInfiniteQuery({
        queryKey: ["favorites"],
        queryFn: ({ pageParam }) => getFavorites(pageParam),
        initialPageParam: 1,
        getNextPageParam: (last) => (last.meta.hasMore ? last.meta.currentPage + 1 : undefined),
    });

    return {
        favorites: query.data?.pages.flatMap((page) => page.activities) ?? [],
        isLoading: query.isLoading,
        error: query.error,
        hasNextPage: query.hasNextPage,
        fetchNextPage: query.fetchNextPage,
        isFetchingNextPage: query.isFetchingNextPage,
    };
}

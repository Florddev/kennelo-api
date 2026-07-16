"use client";

import { useEffect, useState } from "react";
import { useLocale } from "next-intl";
import { useQuery } from "@tanstack/react-query";
import { searchPlaces } from "@workspace/modules/address";

import type { LocationSuggestion } from "../lib/types";

const DEBOUNCE_MS = 300;
const MIN_QUERY_LENGTH = 2;

export function useLocationSuggestions(query: string): {
    suggestions: LocationSuggestion[];
    isFetching: boolean;
} {
    const locale = useLocale();
    const [debouncedQuery, setDebouncedQuery] = useState("");

    useEffect(() => {
        const timeout = setTimeout(() => setDebouncedQuery(query), DEBOUNCE_MS);
        return () => clearTimeout(timeout);
    }, [query]);

    const trimmed = debouncedQuery.trim();

    const { data = [], isFetching } = useQuery({
        queryKey: ["place-search", trimmed, locale],
        queryFn: ({ signal }) => searchPlaces(trimmed, { lang: locale, signal }),
        enabled: trimmed.length >= MIN_QUERY_LENGTH,
        staleTime: 60_000,
    });

    return { suggestions: data, isFetching };
}

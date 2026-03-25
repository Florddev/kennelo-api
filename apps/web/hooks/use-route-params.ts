"use client";

import { useParams, useSearchParams } from "next/navigation";

export function useRouteParams<T extends Record<string, string>>(): T {
    const params = useParams();
    const searchParams = useSearchParams();

    const result: Record<string, string> = {};

    Object.entries(params).forEach(([key, value]) => {
        const stringValue = Array.isArray(value) ? value[0] : value;
        if (stringValue !== undefined) {
            result[key] = stringValue;
        }
    });

    searchParams.forEach((value, key) => {
        result[key] = value;
    });

    return result as T;
}

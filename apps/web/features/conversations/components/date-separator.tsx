"use client";

import { useLocale } from "next-intl";
import { formatDateAdaptive } from "@workspace/common";

export function DateSeparator({
    date,
    displaySeparators = true,
}: {
    date: string;
    displaySeparators?: boolean;
}) {
    const locale = useLocale();
    const label = formatDateAdaptive(date, locale);

    return (
        <div className="mb-4 py-2 flex items-center justify-center gap-3">
            {displaySeparators && <div className="h-px flex-1 bg-border" />}
            <span className="px-1 text-xs font-semibold capitalize text-primary">{label}</span>
            {displaySeparators && <div className="h-px flex-1 bg-border" />}
        </div>
    );
}

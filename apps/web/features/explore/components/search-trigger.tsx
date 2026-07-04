"use client";

import { Search } from "lucide-react";
import { useTranslations } from "next-intl";
import { cn } from "@workspace/ui/lib/utils";

type CompactSearchTriggerProps = {
    summary: string;
    onModify: () => void;
    className?: string;
};

export function CompactSearchTrigger({ summary, onModify, className }: CompactSearchTriggerProps) {
    const t = useTranslations();

    return (
        <button
            data-slot="compact-search-trigger"
            onClick={onModify}
            className={cn(
                "flex-1 flex items-center gap-2 bg-muted/60 rounded-full px-4 py-2 text-start min-w-0",
                className,
            )}
        >
            <Search className="size-3.5 text-muted-foreground shrink-0" />
            <span className="text-sm text-foreground font-medium truncate">{summary}</span>
            <span className="ms-auto text-xs text-secondary font-semibold shrink-0">
                {t("features.search.trigger.modify")}
            </span>
        </button>
    );
}

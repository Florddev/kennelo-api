"use client";

import { Search } from "lucide-react";
import { cn } from "@workspace/ui/lib/utils";

type SearchTriggerProps = {
    location?: string;
    dateDisplay?: string;
    petDisplay?: string;
    onClick: () => void;
    className?: string;
};

export function SearchTrigger({
    location,
    dateDisplay,
    petDisplay,
    onClick,
    className,
}: SearchTriggerProps) {
    const hasValues = location || dateDisplay || petDisplay;

    return (
        <button
            data-slot="search-trigger"
            onClick={onClick}
            className={cn(
                "w-full flex items-center gap-3 bg-background rounded-2xl border border-border shadow-sm px-4 py-3 text-start hover:shadow-md transition-shadow",
                className,
            )}
        >
            <div className="size-9 rounded-xl bg-secondary flex items-center justify-center shrink-0">
                <Search className="size-4 text-secondary-foreground" />
            </div>
            <div className="flex-1 min-w-0">
                {hasValues ? (
                    <>
                        <div className="text-sm font-semibold text-foreground truncate">
                            {[location, dateDisplay, petDisplay].filter(Boolean).join(" · ")}
                        </div>
                        <div className="text-xs text-muted-foreground">Modifier la recherche</div>
                    </>
                ) : (
                    <>
                        <div className="text-sm font-semibold text-foreground">
                            Où garder votre animal ?
                        </div>
                        <div className="text-xs text-muted-foreground">Lieu · Dates · Animal</div>
                    </>
                )}
            </div>
        </button>
    );
}

type CompactSearchTriggerProps = {
    summary: string;
    onModify: () => void;
    className?: string;
};

export function CompactSearchTrigger({ summary, onModify, className }: CompactSearchTriggerProps) {
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
            <span className="ms-auto text-xs text-secondary font-semibold shrink-0">Modifier</span>
        </button>
    );
}

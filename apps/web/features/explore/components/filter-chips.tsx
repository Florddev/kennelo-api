"use client";

import { cn } from "@workspace/ui/lib/utils";

export type FilterChip = {
    id: string;
    label: string;
};

export const EXPLORE_FILTERS: FilterChip[] = [
    { id: "all", label: "Tous" },
    { id: "pro", label: "Professionnels" },
    { id: "particulier", label: "Particuliers" },
    { id: "weekend", label: "Ce week-end" },
    { id: "top-rated", label: "Note 4,5+" },
];

type FilterChipsProps = {
    activeFilter: string;
    onSelect: (id: string) => void;
    filters?: FilterChip[];
    className?: string;
};

export function FilterChips({
    activeFilter,
    onSelect,
    filters = EXPLORE_FILTERS,
    className,
}: FilterChipsProps) {
    return (
        <div
            data-slot="filter-chips"
            className={cn("flex items-center gap-2 overflow-x-auto scrollbar-none px-4", className)}
        >
            {filters.map((filter) => {
                const isActive = filter.id === activeFilter;
                return (
                    <button
                        key={filter.id}
                        onClick={() => onSelect(filter.id)}
                        className={cn(
                            "shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-all border",
                            isActive
                                ? "bg-foreground text-background border-foreground"
                                : "bg-background text-foreground border-border hover:border-foreground/30",
                        )}
                    >
                        {filter.label}
                    </button>
                );
            })}
        </div>
    );
}

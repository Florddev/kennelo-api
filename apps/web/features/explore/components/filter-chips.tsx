"use client";

import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";

export type FilterChip = {
    id: string;
    label: string;
};

type FilterChipsProps = {
    activeFilter: string;
    onSelect: (id: string) => void;
    filters?: FilterChip[];
    className?: string;
};

export function FilterChips({ activeFilter, onSelect, filters, className }: FilterChipsProps) {
    const t = useTranslations();

    const resolvedFilters: FilterChip[] = filters ?? [
        { id: "all", label: t("features.explore.chips.all") },
        { id: "pro", label: t("features.explore.chips.pro") },
        { id: "particulier", label: t("features.explore.chips.particulier") },
        { id: "top-rated", label: t("features.explore.chips.topRated") },
    ];

    return (
        <div
            data-slot="filter-chips"
            className={cn(
                "flex items-center gap-0.5 overflow-x-auto scrollbar-none px-4",
                className,
            )}
        >
            {resolvedFilters.map((filter) => {
                const isActive = filter.id === activeFilter;
                return (
                    <Button
                        size="sm"
                        variant={isActive ? "default" : "flat"}
                        key={filter.id}
                        onClick={() => onSelect(filter.id)}
                        className="text-xs"
                    >
                        {filter.label}
                    </Button>
                );
            })}
        </div>
    );
}

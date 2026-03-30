"use client";

import { ChevronDown, PawPrint, Search, SlidersHorizontal, X } from "lucide-react";
import { useTranslations } from "next-intl";
import { Badge } from "@workspace/ui/components/badge";
import { Button } from "@workspace/ui/components/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from "@workspace/ui/components/dropdown-menu";
import { Input } from "@workspace/ui/components/input";
import { cn } from "@workspace/ui/lib/utils";
import type { AvailableType, SortOption } from "@/features/pets/hooks/use-pets-filters";
import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";

type PetsFilterBarProps = {
    search: string;
    onSearchChange: (value: string) => void;
    typeFilter: number | null;
    onTypeFilterChange: (id: number | null) => void;
    sort: SortOption;
    onSortChange: (sort: SortOption) => void;
    availableTypes: AvailableType[];
    hasActiveFilters: boolean;
    onClearFilters: () => void;
    resultsCount: number;
};

const SORT_OPTIONS: SortOption[] = [
    "newest",
    "name-asc",
    "name-desc",
    "age-youngest",
    "age-oldest",
];

const SORT_KEYS: Record<SortOption, string> = {
    newest: "features.pets.filters.sortNewest",
    "name-asc": "features.pets.filters.sortNameAsc",
    "name-desc": "features.pets.filters.sortNameDesc",
    "age-youngest": "features.pets.filters.sortYoungest",
    "age-oldest": "features.pets.filters.sortOldest",
};

export function PetsFilterBar({
    search,
    onSearchChange,
    typeFilter,
    onTypeFilterChange,
    sort,
    onSortChange,
    availableTypes,
    hasActiveFilters,
    onClearFilters,
    resultsCount,
}: PetsFilterBarProps) {
    const t = useTranslations();
    const typeFilterValue = typeFilter !== null ? typeFilter.toString() : "all";
    const selectedType = availableTypes.find((type) => type.id === typeFilter);

    return (
        <div className="space-y-3 pt-1">
            <div className="flex items-center gap-1.5">
                {availableTypes.length > 1 && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="outline"
                                className={cn(
                                    "rounded-4xl gap-2 shrink-0",
                                    typeFilter !== null &&
                                        "border-foreground bg-foreground text-background hover:bg-foreground/90 hover:text-background",
                                )}
                            >
                                {selectedType ? (
                                    <>
                                        <PetTypeIllustration
                                            code={selectedType.code}
                                            name={selectedType.name}
                                            className="size-4"
                                        />
                                        {selectedType.name}
                                    </>
                                ) : (
                                    <>
                                        <PawPrint className="size-4" />
                                        {t("features.pets.filters.allTypes")}
                                    </>
                                )}
                                <ChevronDown className="size-3.5 opacity-60" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" className="w-52">
                            <DropdownMenuRadioGroup
                                value={typeFilterValue}
                                onValueChange={(v) =>
                                    onTypeFilterChange(v === "all" ? null : Number(v))
                                }
                            >
                                <DropdownMenuRadioItem value="all" className="gap-2.5">
                                    <PawPrint className="size-4 text-muted-foreground shrink-0" />
                                    {t("features.pets.filters.all")}
                                </DropdownMenuRadioItem>
                                {availableTypes.map((type) => (
                                    <DropdownMenuRadioItem
                                        key={type.id}
                                        value={type.id.toString()}
                                        className="gap-2.5"
                                    >
                                        <PetTypeIllustration
                                            code={type.code}
                                            name={type.name}
                                            className="size-4"
                                        />
                                        <span className="flex-1">{type.name}</span>
                                        <span className="text-xs text-muted-foreground">
                                            {type.count}
                                        </span>
                                    </DropdownMenuRadioItem>
                                ))}
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="outline" className="rounded-4xl gap-2 shrink-0">
                            <SlidersHorizontal className="size-4" />
                            {t("features.pets.filters.sort")}
                            {sort !== "newest" && (
                                <Badge
                                    variant="secondary"
                                    className="rounded-full size-5 p-0 flex items-center justify-center text-[10px]"
                                >
                                    1
                                </Badge>
                            )}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" className="w-48">
                        <DropdownMenuRadioGroup
                            value={sort}
                            onValueChange={(v) => onSortChange(v as SortOption)}
                        >
                            {SORT_OPTIONS.map((option) => (
                                <DropdownMenuRadioItem key={option} value={option}>
                                    {t(SORT_KEYS[option])}
                                </DropdownMenuRadioItem>
                            ))}
                        </DropdownMenuRadioGroup>
                    </DropdownMenuContent>
                </DropdownMenu>

                <div className="relative flex-1 max-w-xs">
                    <Search className="absolute start-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none" />
                    <Input
                        className="ps-9 rounded-4xl"
                        placeholder={t("features.pets.searchPlaceholder")}
                        value={search}
                        onChange={(e) => onSearchChange(e.target.value)}
                    />
                </div>

                {hasActiveFilters && (
                    <Button
                        variant="ghost"
                        size="sm"
                        className="rounded-4xl gap-1.5 text-muted-foreground"
                        onClick={onClearFilters}
                    >
                        <X className="size-3.5" />
                        {t("features.pets.filters.clearFilters")}
                    </Button>
                )}
            </div>

            <p className="text-xs text-muted-foreground">
                {t("features.pets.filters.resultsCount", { count: resultsCount })}
            </p>
        </div>
    );
}

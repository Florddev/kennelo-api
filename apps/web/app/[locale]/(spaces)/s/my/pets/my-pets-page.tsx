"use client";

import { Plus, PawPrint, PlusIcon, Search } from "lucide-react";
import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@workspace/ui/components/empty";
import { usePets } from "@/features/pets/hooks/use-pets";
import { usePetsFilters } from "@/features/pets/hooks/use-pets-filters";
import { PetCard, PetCardSkeleton } from "@/features/pets/components/pet-card";
import { PetsFilterBar } from "@/features/pets/components/pets-filter-bar";
import { KHeart } from "@workspace/ui/icons";
import { useScrolled } from "@/hooks/use-scrolled";
import { cn } from "@workspace/ui/lib/utils";
import { useIsMobile } from "@/hooks/use-mobile";
import { ShapeMedia } from "@/components/media/shape-media";

type PetsContentProps = {
    isLoading: boolean;
    pets: ReturnType<typeof usePets>["pets"];
    filteredPets: ReturnType<typeof usePetsFilters>["filteredPets"];
    t: ReturnType<typeof useTranslations>;
};

function PetsContent({ isLoading, pets, filteredPets, t }: PetsContentProps) {
    if (isLoading) {
        return (
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {Array.from({ length: 8 }).map((_, i) => (
                    <PetCardSkeleton key={i} />
                ))}
            </div>
        );
    }

    if (pets.length === 0 || filteredPets.length === 0) {
        return (
            <Empty className="border">
                <EmptyMedia variant="icon">
                    <PawPrint />
                </EmptyMedia>
                <EmptyHeader>
                    <EmptyTitle>
                        {filteredPets.length === 0
                            ? t("features.pets.filters.resultsCount", { count: 0 })
                            : t("features.pets.noPets")}
                    </EmptyTitle>
                    <EmptyDescription>{t("features.pets.noPetsDescription")}</EmptyDescription>
                </EmptyHeader>
                {pets.length === 0 && (
                    <EmptyContent>
                        <Button className="mx-auto gap-2">
                            <Plus className="size-4" />
                            {t("features.pets.addPet")}
                        </Button>
                    </EmptyContent>
                )}
            </Empty>
        );
    }

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 xl:gap-8 xl:gap-y-8">
            {filteredPets.map((pet) => (
                <PetCard key={pet.id} pet={pet} />
            ))}
            <div className="flex gap-4 items-center cursor-pointer">
                <ShapeMedia emptyIcon={PlusIcon} shapeClassName="lg:size-38 xl:size-56" />
                <div className="flex flex-col gap-2">
                    <h1 className="text-2xl font-semibold">Ajouter</h1>
                    <span className="text-sm text-muted-foreground">Créer un nouvel animal</span>
                </div>
            </div>
        </div>
    );
}

export default function MyPetsPage() {
    const t = useTranslations();
    const { pets, isLoading } = usePets();
    const {
        search,
        setSearch,
        typeFilter,
        setTypeFilter,
        sort,
        setSort,
        filteredPets,
        availableTypes,
        hasActiveFilters,
        clearFilters,
    } = usePetsFilters(pets);
    const scrolled = useScrolled(100);
    const isMobile = useIsMobile();

    return (
        <div className="min-h-screen bg-card">
            {/* <div 
                className={cn(
                    "sticky top-0 bg-card flex items-center z-10",
                    scrolled && "border-b",
                )}
            >
                <div className="flex md:justify-between sm:items-center w-full py-2 p-4 sm:pt-6">
                    <h1
                        className={cn(
                            scrolled ? "text-xl" : "text-3xl mt-12",
                            "flex gap-1.5 items-center font-bold tracking-tight transition-all sm:mt-0",
                        )}
                    >
                        <KHeart
                            className={cn("size-12 -ml-1.5 transition-all", scrolled && "size-9")}
                            filled
                            secondaryOpacity={1}
                            secondary="text-secondary"
                        />
                        {t("features.pets.title")}
                    </h1>
                    <div className="ml-auto flex gap-1">
                        <Button
                            className="bg-muted gap-2"
                            variant="secondary"
                            size={isMobile ? "icon-sm" : "default"}
                        >
                            <Plus className="size-4" />
                            {!isMobile && t("features.pets.addPet")}
                        </Button>
                        <Button
                            className="bg-muted gap-2"
                            variant="secondary"
                            size={isMobile ? "icon-sm" : "default"}
                        >
                            <Search className="size-4" />
                            {!isMobile && t('common.actions.search')}
                        </Button>
                    </div>
                </div>
            </div> */}
            <div
                className={cn(
                    "sticky top-0 bg-card flex items-center z-10",
                    scrolled && "border-b",
                )}
            >
                <div className="flex flex-col-reverse md:flex-row md:justify-between sm:items-center w-full py-2 p-4 sm:pt-6">
                    <h1
                        className={cn(
                            "flex gap-1.5 items-center font-bold tracking-tight transition-all sm:mt-0 h-8",
                            scrolled ? "text-xl -mt-8" : "text-3xl mt-4",
                        )}
                    >
                        <KHeart
                            className={cn("size-12 -ml-1.5 transition-all", scrolled && "size-9")}
                            filled
                            secondaryOpacity={1}
                            secondary="text-secondary"
                        />
                        {t("features.pets.title")}
                    </h1>
                    <div className="ml-auto h-8 flex gap-1 items-center">
                        <Button
                            className="bg-muted gap-2"
                            variant="secondary"
                            size={isMobile ? "icon-sm" : "default"}
                        >
                            <Plus className="size-4" />
                            {!isMobile && t("features.pets.addPet")}
                        </Button>
                        <Button
                            className="bg-muted gap-2"
                            variant="secondary"
                            size={isMobile ? "icon-sm" : "default"}
                        >
                            <Search className="size-4" />
                            {!isMobile && t("common.actions.search")}
                        </Button>
                    </div>
                </div>
            </div>

            <div className="pb-6 space-y-6 px-4">
                {!isLoading && pets.length > 0 && (
                    <PetsFilterBar
                        search={search}
                        onSearchChange={setSearch}
                        typeFilter={typeFilter}
                        onTypeFilterChange={setTypeFilter}
                        sort={sort}
                        onSortChange={setSort}
                        availableTypes={availableTypes}
                        hasActiveFilters={hasActiveFilters}
                        onClearFilters={clearFilters}
                        resultsCount={filteredPets.length}
                    />
                )}
                <PetsContent isLoading={isLoading} pets={pets} filteredPets={filteredPets} t={t} />
            </div>
        </div>
    );
}

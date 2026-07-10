"use client";

import { useState } from "react";
import { Plus } from "lucide-react";
import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import { Badge } from "@workspace/ui/components/badge";
import { InputGroup, InputGroupAddon, InputGroupInput } from "@workspace/ui/components/input-group";
import { cn } from "@workspace/ui/lib/utils";
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@workspace/ui/components/empty";
import { isKnownAnimalTypeCode } from "@workspace/modules/pets";
import { usePets } from "@/features/pets/hooks/use-pets";
import { usePetsFilters } from "@/features/pets/hooks/use-pets-filters";
import { PetCard, PetCardSkeleton } from "@/features/pets/components/pet-card";
import { PetTypeIllustration } from "@/features/pets/components/pet-type-illustration";
import { useIsMobile } from "@/hooks/use-mobile";
import { useNavigation } from "@/hooks/use-navigation";
import PageLayout from "@/components/layouts/page-layout";
import { Hearts, MinimalisticMagnifier, Scanner } from "@solar-icons/react";
import { useAuth } from "@/features/auth";
import Link from "next/link";

const ADD_PET_KEY = "features.pets.addPet";

function PetsContent({
    isLoading,
    pets,
    filteredPets,
    onCreatePet,
}: {
    isLoading: boolean;
    pets: ReturnType<typeof usePets>["pets"];
    filteredPets: ReturnType<typeof usePetsFilters>["filteredPets"];
    onCreatePet: () => void;
}) {
    const t = useTranslations();

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
                    <Hearts />
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
                        <Button className="mx-auto gap-2" onClick={onCreatePet}>
                            <Plus className="size-4" />
                            {t(ADD_PET_KEY)}
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
        </div>
    );
}

function PetTypeFilters({
    availableTypes,
    typeFilter,
    setTypeFilter,
}: Pick<ReturnType<typeof usePetsFilters>, "availableTypes" | "typeFilter" | "setTypeFilter">) {
    const t = useTranslations();
    return (
        <div className="flex flex-nowrap gap-1.5 overflow-x-auto scrollbar-none w-full pb-0.5">
            <Badge
                variant={typeFilter === null ? "default" : "flat"}
                size="lg"
                className="text-xs cursor-pointer shrink-0 gap-1.5"
                onClick={() => setTypeFilter(null)}
            >
                {t("features.pets.filters.all")}
            </Badge>
            {availableTypes.map((type) => {
                const typeKey = `features.pets.types.${type.code}` as Parameters<typeof t>[0];
                const label = isKnownAnimalTypeCode(type.code) ? t(typeKey) : type.name;
                return (
                    <Badge
                        key={type.id}
                        variant={typeFilter === type.id ? "default" : "flat"}
                        size="lg"
                        className="text-xs cursor-pointer shrink-0 gap-1.5"
                        onClick={() => setTypeFilter(type.id)}
                    >
                        <PetTypeIllustration code={type.code} name={label} className="size-3.5" />
                        {label}
                    </Badge>
                );
            })}
        </div>
    );
}

function PetsHeaderActions({
    isSearching,
    search,
    setSearch,
    isMobile,
    livePetHref,
    onSearchOpen,
    onSearchClose,
    onCreatePet,
}: {
    isSearching: boolean;
    search: string;
    setSearch: (value: string) => void;
    isMobile: boolean;
    livePetHref: string;
    onSearchOpen: () => void;
    onSearchClose: () => void;
    onCreatePet: () => void;
}) {
    const t = useTranslations();
    const buttonSize = isMobile ? "icon-sm" : "default";
    return (
        <>
            <div
                className={cn(
                    "flex justify-end w-fit",
                    isSearching && "md:w-[calc(100%-4.5rem)] md:absolute md:left-0 md:px-4",
                )}
            >
                <InputGroup
                    className={cn(
                        "h-7 md:h-9 gap-1 w-full transition-all duration-300 border-none bg-muted has-[[data-slot=input-group-control]:focus-visible]:ring-[2px]",
                        !isSearching && "size-8",
                    )}
                    onClick={!isSearching ? onSearchOpen : undefined}
                    autoFocus={isSearching}
                >
                    <InputGroupInput
                        placeholder={t("common.actions.search")}
                        className="placeholder:text-sm"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                    <InputGroupAddon
                        align="inline-start"
                        className={cn("transition-all", !isSearching && "pl-2")}
                    >
                        <MinimalisticMagnifier className="size-3.5 text-primary" />
                    </InputGroupAddon>
                </InputGroup>
            </div>
            {isSearching ? (
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={onSearchClose}
                    className="md:absolute md:right-0"
                >
                    {t("common.actions.cancel")}
                </Button>
            ) : (
                <Button className="gap-2" variant="flat" size={buttonSize} onClick={onCreatePet}>
                    <Plus className="size-3.5" />
                    {!isMobile && t(ADD_PET_KEY)}
                </Button>
            )}
            <Button className="gap-2 hidden" variant="flat" size={buttonSize} asChild>
                <Link href={livePetHref}>
                    <Scanner className="size-3.5" />
                    {!isMobile && t(ADD_PET_KEY)}
                </Link>
            </Button>
        </>
    );
}

function GuestPrompt({ loginHref }: { loginHref: string }) {
    const t = useTranslations();
    return (
        <div className="flex flex-col gap-4 py-1 text-sm">
            <div className="flex flex-col gap-1">
                <p className="text-lg text-primary font-semibold">
                    {t("features.pets.please-login")}
                </p>
                <span className="text-muted-foreground">
                    {t("features.pets.please-login-description")}
                </span>
            </div>
            <Button variant="default" className="w-fit px-5" asChild>
                <Link href={loginHref}>{t("common.actions.login")}</Link>
            </Button>
        </div>
    );
}

export default function MyPetsPage() {
    const t = useTranslations();
    const { isAuthenticated } = useAuth();
    const { routes, push } = useNavigation();
    const { pets, isLoading } = usePets();
    const { search, setSearch, typeFilter, setTypeFilter, filteredPets, availableTypes } =
        usePetsFilters(pets);
    const isMobile = useIsMobile();
    const [hideTitle, setHideTitle] = useState(false);
    const [isSearching, setIsSearching] = useState(false);

    const handleCreatePet = () => push(`${routes.MyPets()}/new`);

    const handleSearchOpen = () => {
        setIsSearching(true);
        setHideTitle(true);
    };

    const handleSearchClose = () => {
        setIsSearching(false);
        setHideTitle(false);
        setSearch("");
    };

    const hasFilters = isAuthenticated && !isLoading && pets.length > 0;

    return (
        <div className="p-4 md:p-6">
            <PageLayout
                Icon={Hearts}
                title={t("features.pets.title")}
                headerTopClassName={cn(isSearching && "w-full")}
                hideTitle={hideTitle}
                headerTop={
                    isAuthenticated && (
                        <PetsHeaderActions
                            isSearching={isSearching}
                            search={search}
                            setSearch={setSearch}
                            isMobile={isMobile}
                            livePetHref={routes.LivePet()}
                            onSearchOpen={handleSearchOpen}
                            onSearchClose={handleSearchClose}
                            onCreatePet={handleCreatePet}
                        />
                    )
                }
                headerBottom={
                    hasFilters ? (
                        <PetTypeFilters
                            availableTypes={availableTypes}
                            typeFilter={typeFilter}
                            setTypeFilter={setTypeFilter}
                        />
                    ) : null
                }
            >
                {!isAuthenticated ? (
                    <GuestPrompt loginHref={routes.Login()} />
                ) : (
                    <PetsContent
                        isLoading={isLoading}
                        pets={pets}
                        filteredPets={filteredPets}
                        onCreatePet={handleCreatePet}
                    />
                )}
            </PageLayout>
        </div>
    );
}

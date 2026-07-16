"use client";

import Image from "next/image";
import { useTranslations } from "next-intl";

import { cn } from "@workspace/ui/lib/utils";

import { useMobileSearch } from "../../hooks/use-mobile-search";
import { MobileSearchOverlay } from "./mobile-search-overlay";
import { Magnifier, Tuning2 } from "@solar-icons/react";

function MobileSearchTrigger({
    location,
    dateDisplay,
    totalPets,
    selectedPetTypes,
    onClick,
}: {
    location: string;
    dateDisplay: string | null;
    totalPets: number;
    selectedPetTypes: string[];
    onClick: () => void;
}) {
    const t = useTranslations();

    const subtitle = [
        dateDisplay || t("features.search.dates"),
        totalPets > 0
            ? t("features.search.pets.count", { count: totalPets })
            : t("features.search.pets.label"),
    ].join(" · ");

    return (
        <button
            data-slot="mobile-search-trigger"
            onClick={onClick}
            className="w-full flex items-center bg-card rounded-full shadow-md ring-1 ring-border/30 p-1.5 text-start active:scale-[0.98] transition-transform"
        >
            <div className="size-9 rounded-full bg-primary flex items-center justify-center shrink-0">
                <Magnifier className="size-4 text-primary-foreground" />
            </div>

            <div className="flex-1 min-w-0 px-3">
                <div
                    className={cn(
                        "text-sm font-semibold truncate leading-tight",
                        location ? "text-foreground" : "text-muted-foreground",
                    )}
                >
                    {location || t("features.search.destination")}
                </div>
                <div className="flex items-center gap-1.5 mt-0.5">
                    {selectedPetTypes.length > 0 && (
                        <div className="flex items-center -space-x-1 shrink-0">
                            {selectedPetTypes.slice(0, 2).map((type) => (
                                <div
                                    key={type}
                                    className="size-4 rounded-full bg-secondary/20 ring-1 ring-background flex items-center justify-center"
                                >
                                    <Image
                                        src={`/illustrations/pets/${type}.svg`}
                                        width={10}
                                        height={10}
                                        alt={type}
                                    />
                                </div>
                            ))}
                        </div>
                    )}
                    <span className="text-xs text-muted-foreground truncate">{subtitle}</span>
                </div>
            </div>
            <div className="size-9 rounded-full flex items-center justify-center shrink-0">
                <Tuning2 className="size-4" />
            </div>
        </button>
    );
}

export default function MobileSearch() {
    const {
        locationInputRef,
        isOverlayOpen,
        activeCollapsible,
        locationSearchActive,
        location,
        dateRange,
        petCounts,
        totalPets,
        selectedPetTypes,
        filteredSuggestions,
        dateDisplay,
        isLastStep,
        formatDate,
        openOverlay,
        closeOverlay,
        setLocation,
        setDateRange,
        toggleCollapsible,
        selectLocation,
        clearLocation,
        adjustPetCount,
        clearAll,
        handleNext,
        handleSearch,
        selectRecentSearch,
        setLocationSearchActive,
    } = useMobileSearch();

    return (
        <>
            <MobileSearchTrigger
                location={location}
                dateDisplay={dateDisplay}
                totalPets={totalPets}
                selectedPetTypes={selectedPetTypes}
                onClick={openOverlay}
            />

            {isOverlayOpen && (
                <MobileSearchOverlay
                    activeCollapsible={activeCollapsible}
                    locationSearchActive={locationSearchActive}
                    location={location}
                    dateRange={dateRange}
                    petCounts={petCounts}
                    totalPets={totalPets}
                    filteredSuggestions={filteredSuggestions}
                    dateDisplay={dateDisplay}
                    isLastStep={isLastStep}
                    locationInputRef={locationInputRef}
                    formatDate={formatDate}
                    onClose={closeOverlay}
                    onToggleCollapsible={toggleCollapsible}
                    onSelectLocation={selectLocation}
                    onSelectNearby={setLocation}
                    onClearLocation={clearLocation}
                    onChangeLocation={setLocation}
                    onSelectDateRange={setDateRange}
                    onAdjustPet={adjustPetCount}
                    onClearAll={clearAll}
                    onSelectRecentSearch={selectRecentSearch}
                    onNext={handleNext}
                    onSearch={handleSearch}
                    onSetLocationSearchActive={setLocationSearchActive}
                />
            )}
        </>
    );
}

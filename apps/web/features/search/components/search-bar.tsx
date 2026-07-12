"use client";

import { cn } from "@workspace/ui/lib/utils";

import { useSearchBar } from "../hooks/use-search-bar";
import { LocationPanel } from "./panels/location-panel";
import { DatesPanel } from "./panels/dates-panel";
import { PetsPanel } from "./panels/pets-panel";
import { SearchBarLocation } from "./search-bar-location";
import { SearchBarDates } from "./search-bar-dates";
import { SearchBarPets } from "./search-bar-pets";
import { SearchBarSubmit } from "./search-bar-submit";
import { SearchBarDivider } from "./search-bar-divider";

export default function SearchBar({ className }: { className?: string }) {
    const {
        containerRef,
        locationInputRef,
        activePanel,
        setActivePanel,
        togglePanel,
        location,
        setLocation,
        dateRange,
        setDateRange,
        petCounts,
        isExpanded,
        totalPets,
        selectedPetTypes,
        filteredSuggestions,
        dateDisplay,
        formatDate,
        selectLocation,
        clearLocation,
        adjustPetCount,
        handleSearch,
    } = useSearchBar();

    return (
        <div ref={containerRef} className={cn("relative w-full max-w-3xl", className)}>
            <div
                data-slot="search-bar"
                data-expanded={isExpanded || undefined}
                className={cn(
                    "flex items-center bg-background/80 backdrop-blur-sm rounded-full transition-all duration-200 ring-1",
                    isExpanded
                        ? "shadow-lg ring-secondary/40"
                        : "shadow-sm hover:shadow-md ring-border hover:ring-secondary/30",
                )}
            >
                <SearchBarLocation
                    activePanel={activePanel}
                    isExpanded={isExpanded}
                    location={location}
                    onChange={setLocation}
                    onFocus={() => setActivePanel("location")}
                    onClear={clearLocation}
                    inputRef={locationInputRef}
                />

                <SearchBarDivider
                    hidden={isExpanded && (activePanel === "location" || activePanel === "dates")}
                />

                <SearchBarDates
                    activePanel={activePanel}
                    isExpanded={isExpanded}
                    dateDisplay={dateDisplay}
                    onClick={() => togglePanel("dates")}
                />

                <SearchBarDivider
                    hidden={isExpanded && (activePanel === "dates" || activePanel === "pets")}
                />

                <SearchBarPets
                    activePanel={activePanel}
                    isExpanded={isExpanded}
                    totalPets={totalPets}
                    petCounts={petCounts}
                    selectedPetTypes={selectedPetTypes}
                    onClick={() => togglePanel("pets")}
                />

                <SearchBarSubmit isExpanded={isExpanded} onClick={handleSearch} />
            </div>

            {activePanel === "location" && (
                <LocationPanel
                    location={location}
                    filteredSuggestions={filteredSuggestions}
                    onSelect={selectLocation}
                    formatDate={formatDate}
                />
            )}

            {activePanel === "dates" && (
                <DatesPanel dateRange={dateRange} onSelect={setDateRange} />
            )}

            {activePanel === "pets" && (
                <PetsPanel petCounts={petCounts} onAdjust={adjustPetCount} />
            )}
        </div>
    );
}

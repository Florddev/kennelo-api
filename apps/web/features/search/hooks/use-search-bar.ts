"use client";

import { useState, useRef, useEffect } from "react";
import { useRouter } from "next/navigation";
import { useLocale } from "next-intl";
import type { DateRange } from "react-day-picker";

import type {
    ActivePanel,
    LocationSuggestion,
    PetCounts,
    PetType,
    SelectedPlace,
} from "../lib/types";
import { PET_TYPES } from "../lib/constants";
import { useLocationSuggestions } from "./use-location-suggestions";

export function useSearchBar() {
    const locale = useLocale();
    const router = useRouter();
    const containerRef = useRef<HTMLDivElement>(null);
    const locationInputRef = useRef<HTMLInputElement>(null);

    const [activePanel, setActivePanel] = useState<ActivePanel>(null);
    const [location, setLocation] = useState("");
    const [selectedPlace, setSelectedPlace] = useState<SelectedPlace | null>(null);
    const [dateRange, setDateRange] = useState<DateRange | undefined>();
    const [petCounts, setPetCounts] = useState<PetCounts>({
        dog: 0,
        cat: 0,
        bird: 0,
        reptile: 0,
    });

    const isExpanded = activePanel !== null;
    const totalPets = Object.values(petCounts).reduce((sum, n) => sum + n, 0);
    const selectedPetTypes = PET_TYPES.filter((t) => petCounts[t] > 0);

    const { suggestions: filteredSuggestions, isFetching: isSearchingLocation } =
        useLocationSuggestions(location);

    useEffect(() => {
        if (activePanel === "location") {
            locationInputRef.current?.focus();
        }
    }, [activePanel]);

    useEffect(() => {
        function handlePointerDown(event: PointerEvent) {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setActivePanel(null);
            }
        }
        document.addEventListener("pointerdown", handlePointerDown);
        return () => document.removeEventListener("pointerdown", handlePointerDown);
    }, []);

    function formatDate(date: Date) {
        return new Intl.DateTimeFormat(locale, { month: "short", day: "numeric" }).format(date);
    }

    function getDateDisplay() {
        if (!dateRange?.from) return null;
        if (!dateRange.to) return formatDate(dateRange.from);
        return `${formatDate(dateRange.from)} – ${formatDate(dateRange.to)}`;
    }

    function togglePanel(panel: ActivePanel) {
        setActivePanel((prev) => (prev === panel ? null : panel));
    }

    function adjustPetCount(type: PetType, delta: number) {
        setPetCounts((prev) => ({ ...prev, [type]: Math.max(0, prev[type] + delta) }));
    }

    function selectLocation(suggestion: LocationSuggestion) {
        setLocation(suggestion.getLabel());
        setSelectedPlace({
            label: suggestion.getLabel(),
            latitude: suggestion.latitude,
            longitude: suggestion.longitude,
            radiusKm: suggestion.radiusKm,
        });
        setActivePanel("dates");
    }

    function clearLocation() {
        setLocation("");
        setSelectedPlace(null);
        locationInputRef.current?.focus();
    }

    function handleSearch() {
        const params = new URLSearchParams();
        if (location) params.set("location", location);
        if (selectedPlace && selectedPlace.label === location) {
            params.set("lat", String(selectedPlace.latitude));
            params.set("lng", String(selectedPlace.longitude));
            params.set("radius", String(selectedPlace.radiusKm));
        }
        if (dateRange?.from) params.set("dateFrom", dateRange.from.toISOString().slice(0, 10));
        if (dateRange?.to) params.set("dateTo", dateRange.to.toISOString().slice(0, 10));
        PET_TYPES.forEach((type) => {
            if (petCounts[type] > 0) params.set(type, String(petCounts[type]));
        });
        setActivePanel(null);
        router.push(`/${locale}/explore/results?${params.toString()}`);
    }

    return {
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
        isSearchingLocation,
        dateDisplay: getDateDisplay(),
        formatDate,
        selectLocation,
        clearLocation,
        adjustPetCount,
        handleSearch,
    };
}

"use client";

import { useState, useRef, useEffect } from "react";
import { useRouter } from "next/navigation";
import { useLocale } from "next-intl";
import type { DateRange } from "react-day-picker";

import type {
    LocationSuggestion,
    PetCounts,
    PetType,
    RecentSearch,
    SelectedPlace,
} from "../lib/types";
import { PET_TYPES } from "../lib/constants";
import { useLocationSuggestions } from "./use-location-suggestions";

export type MobileCollapsible = "location" | "dates" | "pets" | null;

type UseMobileSearchOptions = {
    initialLocation?: string;
    initialDateFrom?: string;
    initialDateTo?: string;
    initialPetCounts?: Record<string, number>;
};

function buildInitialLocation(opt?: string): string {
    if (opt !== undefined) return opt;
    if (typeof window === "undefined") return "";
    return new URLSearchParams(window.location.search).get("location") ?? "";
}

function buildInitialDateRange(dateFrom?: string, dateTo?: string): DateRange | undefined {
    if (dateFrom !== undefined) {
        if (!dateFrom) return undefined;
        return { from: new Date(dateFrom), to: dateTo ? new Date(dateTo) : undefined };
    }
    if (typeof window === "undefined") return undefined;
    const params = new URLSearchParams(window.location.search);
    const from = params.get("dateFrom");
    const to = params.get("dateTo");
    if (!from) return undefined;
    return { from: new Date(from), to: to ? new Date(to) : undefined };
}

function buildInitialPetCounts(opt?: Record<string, number>): PetCounts {
    const initial: PetCounts = { dog: 0, cat: 0, bird: 0, reptile: 0 };
    if (opt !== undefined) {
        PET_TYPES.forEach((type) => {
            const count = opt[type];
            if (count && count > 0) initial[type] = count;
        });
        return initial;
    }
    if (typeof window === "undefined") return initial;
    const params = new URLSearchParams(window.location.search);
    PET_TYPES.forEach((type) => {
        const val = params.get(type);
        if (val !== null) {
            const count = parseInt(val, 10);
            if (count > 0) initial[type] = count;
        }
    });
    return initial;
}

export function useMobileSearch(options?: UseMobileSearchOptions) {
    const locale = useLocale();
    const router = useRouter();
    const locationInputRef = useRef<HTMLInputElement>(null);

    const [isOverlayOpen, setIsOverlayOpen] = useState(false);
    const [activeCollapsible, setActiveCollapsible] = useState<MobileCollapsible>("location");
    const [locationSearchActive, setLocationSearchActive] = useState(false);

    const [location, setLocation] = useState(() => buildInitialLocation(options?.initialLocation));
    const [selectedPlace, setSelectedPlace] = useState<SelectedPlace | null>(null);
    const [dateRange, setDateRange] = useState<DateRange | undefined>(() =>
        buildInitialDateRange(options?.initialDateFrom, options?.initialDateTo),
    );
    const [petCounts, setPetCounts] = useState<PetCounts>(() =>
        buildInitialPetCounts(options?.initialPetCounts),
    );

    const totalPets = Object.values(petCounts).reduce((sum, n) => sum + n, 0);
    const selectedPetTypes = PET_TYPES.filter((t) => petCounts[t] > 0);

    const { suggestions: filteredSuggestions, isFetching: isSearchingLocation } =
        useLocationSuggestions(location);

    useEffect(() => {
        if (locationSearchActive) {
            setTimeout(() => locationInputRef.current?.focus(), 50);
        }
    }, [locationSearchActive]);

    function formatDate(date: Date) {
        return new Intl.DateTimeFormat(locale, { month: "short", day: "numeric" }).format(date);
    }

    function getDateDisplay() {
        if (!dateRange?.from) return null;
        if (!dateRange.to) return formatDate(dateRange.from);
        return `${formatDate(dateRange.from)} – ${formatDate(dateRange.to)}`;
    }

    function openOverlay() {
        setIsOverlayOpen(true);
    }

    function closeOverlay() {
        setIsOverlayOpen(false);
        setActiveCollapsible(null);
        setLocationSearchActive(false);
    }

    function toggleCollapsible(panel: MobileCollapsible) {
        setActiveCollapsible((prev) => (prev === panel ? null : panel));
        setLocationSearchActive(false);
    }

    function selectLocation(suggestion: LocationSuggestion) {
        setLocation(suggestion.getLabel());
        setSelectedPlace({
            label: suggestion.getLabel(),
            latitude: suggestion.latitude,
            longitude: suggestion.longitude,
            radiusKm: suggestion.radiusKm,
        });
        setLocationSearchActive(false);
        setActiveCollapsible("dates");
    }

    function clearLocation() {
        setLocation("");
        setSelectedPlace(null);
        locationInputRef.current?.focus();
    }

    function adjustPetCount(type: PetType, delta: number) {
        setPetCounts((prev) => ({ ...prev, [type]: Math.max(0, prev[type] + delta) }));
    }

    function clearAll() {
        setLocation("");
        setSelectedPlace(null);
        setDateRange(undefined);
        setPetCounts({ dog: 0, cat: 0, bird: 0, reptile: 0 });
        setActiveCollapsible(null);
    }

    function handleNext() {
        if (activeCollapsible === "location") {
            setActiveCollapsible("dates");
        } else if (activeCollapsible === "dates") {
            setActiveCollapsible("pets");
        } else {
            handleSearch();
        }
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
        closeOverlay();
        router.push(`/${locale}/explore/results?${params.toString()}`);
    }

    function selectRecentSearch(recent: RecentSearch) {
        const params = new URLSearchParams();
        params.set("location", recent.location);
        params.set("dateFrom", recent.dateFrom.toISOString().slice(0, 10));
        params.set("dateTo", recent.dateTo.toISOString().slice(0, 10));
        closeOverlay();
        router.push(`/${locale}/explore/results?${params.toString()}`);
    }

    const isLastStep = activeCollapsible === "pets" || activeCollapsible === null;

    return {
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
        isSearchingLocation,
        dateDisplay: getDateDisplay(),
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
    };
}

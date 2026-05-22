"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { ChevronLeft, SlidersHorizontal, Target, RefreshCw, MapPin } from "lucide-react";
import { Drawer as DrawerPrimitive } from "vaul";

import { cn } from "@workspace/ui/lib/utils";
import { Button } from "@workspace/ui/components/button";

import { useNavVisibility } from "@/providers/navigation-visibility-provider";
import { CompactSearchTrigger } from "@/features/explore/components/search-trigger";
import { FilterChips } from "@/features/explore/components/filter-chips";
import { HostCard } from "@/features/explore/components/host-card";
import { useSearchResults } from "@/features/explore/hooks/use-search-results";
import { useMobileSearch } from "@/features/search/hooks/use-mobile-search";
import { MobileSearchOverlay } from "@/features/search/components/mobile/mobile-search-overlay";

const SNAP_POINTS: (number | string)[] = [0.08, 0.5, 0.95];

type ResultsPageProps = {
    location: string;
    dateFrom: string;
    dateTo: string;
    petCounts: Record<string, number>;
};

function MapPlaceholder({ onSearchArea }: { onSearchArea: () => void }) {
    const [showToast, setShowToast] = useState(false);

    function handleMapInteraction() {
        setShowToast(true);
        setTimeout(() => setShowToast(false), 2500);
    }

    return (
        <div
            className="relative w-full h-full bg-[#e8e8e0] overflow-hidden cursor-grab active:cursor-grabbing"
            onPointerDown={handleMapInteraction}
        >
            <svg
                className="absolute inset-0 w-full h-full opacity-30"
                xmlns="http://www.w3.org/2000/svg"
            >
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#999" strokeWidth="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>

            <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div className="bg-background/60 backdrop-blur-sm rounded-2xl px-4 py-2">
                    <p className="text-xs text-muted-foreground font-medium">Carte interactive</p>
                </div>
            </div>

            <div className="absolute bottom-20 start-1/2 -translate-x-1/2 flex items-center gap-3 pointer-events-none">
                <div className="flex items-center justify-center">
                    <div className="size-6 rounded-full bg-foreground flex items-center justify-center shadow-lg">
                        <span className="text-[10px] font-bold text-background">28€</span>
                    </div>
                </div>
                <div className="flex items-center justify-center">
                    <div className="size-6 rounded-full bg-foreground flex items-center justify-center shadow-lg">
                        <span className="text-[10px] font-bold text-background">32€</span>
                    </div>
                </div>
            </div>

            {showToast && (
                <div className="absolute top-4 start-1/2 -translate-x-1/2 z-10 pointer-events-none">
                    <button
                        onClick={onSearchArea}
                        className="flex items-center gap-2 bg-foreground text-background text-xs font-semibold px-4 py-2.5 rounded-full shadow-xl pointer-events-auto"
                    >
                        <RefreshCw className="size-3.5" />
                        Rechercher dans cette zone
                    </button>
                </div>
            )}

            <button
                className="absolute bottom-20 end-4 size-10 bg-background rounded-full shadow-lg flex items-center justify-center hover:bg-muted transition-colors"
                aria-label="Ma position"
            >
                <Target className="size-4 text-foreground" />
            </button>
        </div>
    );
}

function EmptyResults({ onExpand, onModify }: { onExpand: () => void; onModify: () => void }) {
    return (
        <div className="flex flex-col items-center justify-center gap-4 py-12 px-6 text-center">
            <div className="size-16 rounded-full bg-muted flex items-center justify-center">
                <MapPin className="size-7 text-muted-foreground" />
            </div>
            <div>
                <h3 className="font-bold text-base mb-1">Aucun hôte trouvé</h3>
                <p className="text-sm text-muted-foreground">
                    {"Essayez d'élargir votre zone de recherche ou de modifier vos critères."}
                </p>
            </div>
            <div className="flex flex-col gap-2 w-full max-w-xs">
                <Button onClick={onExpand} className="rounded-full w-full">
                    Élargir la zone
                </Button>
                <Button onClick={onModify} variant="outline" className="rounded-full w-full">
                    Modifier les critères
                </Button>
            </div>
        </div>
    );
}

export default function ExploreResultsPage({
    location,
    dateFrom,
    dateTo,
    petCounts,
}: ResultsPageProps) {
    const router = useRouter();
    const { setBottomNavbarVisible } = useNavVisibility();

    const [activeFilter, setActiveFilter] = useState("all");
    const [snap, setSnap] = useState<number | string | null>(0.5);
    const [highlightedId, setHighlightedId] = useState<string | null>(null);

    const { establishments } = useSearchResults({
        location,
        dateFrom,
        dateTo,
        animalCounts: petCounts,
    });

    const {
        isOverlayOpen,
        openOverlay,
        closeOverlay,
        activeCollapsible,
        locationSearchActive,
        location: searchLocation,
        dateRange,
        petCounts: searchPetCounts,
        totalPets,
        filteredSuggestions,
        dateDisplay,
        isLastStep,
        locationInputRef,
        formatDate,
        toggleCollapsible,
        selectLocation,
        clearLocation,
        setLocation,
        setDateRange,
        adjustPetCount,
        clearAll,
        handleNext,
        handleSearch,
        selectRecentSearch,
        setLocationSearchActive,
    } = useMobileSearch({
        initialLocation: location,
        initialDateFrom: dateFrom,
        initialDateTo: dateTo,
        initialPetCounts: petCounts,
    });

    function getFilteredHosts() {
        if (activeFilter === "pro") return establishments.filter((e) => e.isProfessional);
        if (activeFilter === "particulier") return establishments.filter((e) => !e.isProfessional);
        return establishments;
    }
    const filteredHosts = getFilteredHosts();

    useEffect(() => {
        setBottomNavbarVisible(false);
        return () => setBottomNavbarVisible(true);
    }, [setBottomNavbarVisible]);

    const petSummary = Object.entries(petCounts)
        .filter(([, count]) => count > 0)
        .map(([type, count]) => `${count} ${type}`)
        .join(", ");

    const searchSummary = [
        location || "Carhaix",
        dateFrom && dateTo ? `${formatShortDate(dateFrom)}–${formatShortDate(dateTo)}` : "",
        petSummary,
    ]
        .filter(Boolean)
        .join(" · ");

    function formatShortDate(iso: string) {
        if (!iso) return "";
        const d = new Date(iso);
        return new Intl.DateTimeFormat("fr", { day: "numeric", month: "short" }).format(d);
    }

    return (
        <div className="fixed inset-0 z-40 flex flex-col bg-background overflow-hidden">
            <div className="shrink-0 bg-background border-b border-border/40 px-3 py-2.5 flex items-center gap-2">
                <button
                    onClick={() => router.back()}
                    className="size-9 rounded-full bg-muted flex items-center justify-center shrink-0 hover:bg-muted/70 transition-colors"
                >
                    <ChevronLeft className="size-4" />
                </button>

                <CompactSearchTrigger summary={searchSummary} onModify={openOverlay} />

                <button
                    className="size-9 rounded-full bg-muted flex items-center justify-center shrink-0 hover:bg-muted/70 transition-colors relative"
                    aria-label="Filtres"
                >
                    <SlidersHorizontal className="size-4" />
                    <span className="absolute top-1 end-1 size-2 rounded-full bg-secondary" />
                </button>
            </div>

            <div className="shrink-0 py-2">
                <FilterChips activeFilter={activeFilter} onSelect={setActiveFilter} />
            </div>

            <div className="flex-1 relative overflow-hidden">
                <MapPlaceholder onSearchArea={() => {}} />

                <DrawerPrimitive.Root
                    snapPoints={SNAP_POINTS}
                    activeSnapPoint={snap}
                    setActiveSnapPoint={setSnap}
                    modal={false}
                    open
                >
                    <DrawerPrimitive.Content
                        className={cn(
                            "fixed bottom-0 start-0 end-0 z-50 flex flex-col bg-card rounded-t-3xl shadow-2xl ring-1 ring-border/20",
                            "outline-none",
                        )}
                    >
                        <div className="mx-auto mt-3 mb-2 w-10 h-1 rounded-full bg-muted shrink-0" />

                        <div className="flex items-center justify-between px-4 pb-3 shrink-0">
                            <h2 className="font-bold text-base">{filteredHosts.length} hôtes</h2>
                            <button className="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors">
                                <span>Pertinence</span>
                                <span className="text-xs">▼</span>
                            </button>
                        </div>

                        <div
                            className={cn(
                                "overflow-y-auto",
                                snap === SNAP_POINTS[0] ? "hidden" : "flex-1",
                            )}
                        >
                            {filteredHosts.length === 0 ? (
                                <EmptyResults onExpand={() => {}} onModify={openOverlay} />
                            ) : (
                                <>
                                    <div className="px-4 pb-1 flex items-center justify-between">
                                        <p className="text-xs text-muted-foreground">
                                            dans un rayon de 20 km ·{" "}
                                            <button className="underline underline-offset-2 hover:text-foreground transition-colors">
                                                Élargir
                                            </button>
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Trier · Pertinence
                                        </p>
                                    </div>

                                    <div className="flex flex-col divide-y divide-border/40 pb-8">
                                        {filteredHosts.map((host) => (
                                            <div key={host.id} className="px-2">
                                                <HostCard
                                                    host={host}
                                                    variant="horizontal"
                                                    highlighted={highlightedId === host.id}
                                                    onClick={() =>
                                                        setHighlightedId((prev) =>
                                                            prev === host.id ? null : host.id,
                                                        )
                                                    }
                                                />
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}
                        </div>
                    </DrawerPrimitive.Content>
                </DrawerPrimitive.Root>
            </div>

            {isOverlayOpen && (
                <MobileSearchOverlay
                    activeCollapsible={activeCollapsible}
                    locationSearchActive={locationSearchActive}
                    location={searchLocation}
                    dateRange={dateRange}
                    petCounts={searchPetCounts}
                    totalPets={totalPets}
                    filteredSuggestions={filteredSuggestions}
                    dateDisplay={dateDisplay}
                    isLastStep={isLastStep}
                    locationInputRef={locationInputRef}
                    formatDate={formatDate}
                    onClose={closeOverlay}
                    onToggleCollapsible={toggleCollapsible}
                    onSelectLocation={selectLocation}
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
        </div>
    );
}

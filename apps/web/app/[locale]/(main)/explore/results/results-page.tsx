"use client";

import { useState, useEffect, useMemo, useRef } from "react";
import { useRouter } from "next/navigation";
import { ChevronLeft, SlidersHorizontal, MapPin, X } from "lucide-react";
import { useTranslations, useFormatter } from "next-intl";
import { Drawer as DrawerPrimitive } from "vaul";

import { cn } from "@workspace/ui/lib/utils";
import { Button } from "@workspace/ui/components/button";
import {
    Map,
    MapMarker,
    MarkerContent,
    MapControls,
    useMap,
    type MapRef,
} from "@workspace/ui/components/mapcn";
import type { ActivityModel } from "@workspace/modules/activities";

import { useNavVisibility } from "@/providers/navigation-visibility-provider";
import { useNavigation } from "@/hooks/use-navigation";
import { CompactSearchTrigger } from "@/features/explore/components/search-trigger";
import { FilterChips } from "@/features/explore/components/filter-chips";
import { HostCard } from "@/features/explore/components/host-card";
import { useSearchResults } from "@/features/explore/hooks/use-search-results";
import { useMobileSearch } from "@/features/search/hooks/use-mobile-search";
import { MobileSearchOverlay } from "@/features/search/components/mobile/mobile-search-overlay";
import { CrownStar, PointOnMap } from "@solar-icons/react";
import { PET_TYPES } from "@/features/search";

const SNAP_MIN = "70px";
const SNAP_MID = 0.45;
const DEFAULT_CENTER: [number, number] = [1.8883, 46.6034];
const GEOCODE_DEFAULT_RADIUS = 25;

type SearchCoords = { lat: number; lng: number };
type MapBounds = { north: number; south: number; east: number; west: number };
type SearchArea = { coords: SearchCoords; bounds: MapBounds | null; radius: number };

function useGeocodeLocation(query: string): [number, number] | null {
    const [result, setResult] = useState<{ query: string; center: [number, number] } | null>(null);

    useEffect(() => {
        if (!query) return;
        let cancelled = false;

        fetch(
            `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=1`,
        )
            .then((res) => res.json())
            .then((data: Array<{ lat: string; lon: string }>) => {
                if (!cancelled && data[0]) {
                    setResult({
                        query,
                        center: [parseFloat(data[0].lat), parseFloat(data[0].lon)],
                    });
                }
            })
            .catch(() => {});

        return () => {
            cancelled = true;
        };
    }, [query]);

    return result?.query === query ? result.center : null;
}

function useUserLocation(): { lat: number; lng: number } | null {
    const [coords, setCoords] = useState<{ lat: number; lng: number } | null>(null);

    useEffect(() => {
        if (typeof navigator === "undefined" || !navigator.geolocation || !navigator.permissions)
            return;

        navigator.permissions
            // eslint-disable-next-line sonarjs/no-intrusive-permissions
            .query({ name: "geolocation" })
            .then((result) => {
                if (result.state === "granted") {
                    // eslint-disable-next-line sonarjs/no-intrusive-permissions
                    navigator.geolocation.getCurrentPosition(
                        (pos) => setCoords({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
                        () => {},
                    );
                }
            })
            .catch(() => {});
    }, []);

    return coords;
}

function haversineKm(lat1: number, lng1: number, lat2: number, lng2: number): number {
    const R = 6371;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLng = ((lng2 - lng1) * Math.PI) / 180;
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos((lat1 * Math.PI) / 180) *
            Math.cos((lat2 * Math.PI) / 180) *
            Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function PriceMarkerIcon({
    price,
    highlighted,
    isProfessional,
}: {
    price: number | null;
    highlighted: boolean;
    isProfessional: boolean;
}) {
    return (
        <div
            className={cn(
                "flex items-center gap-0.5 px-1.5 py-1 rounded-full text-xs font-bold border border-transparent shadow-md whitespace-nowrap cursor-pointer transition-transform bg-card/80 border border-card backdrop-blur-sm",
                highlighted && "scale-110 ring-2 ring-secondary z-100",
                isProfessional && "bg-primary/90 border-primary text-primary-foreground",
            )}
        >
            {isProfessional && (
                <CrownStar weight="Bold" className="size-3 text-secondary shrink-0" />
            )}
            {price !== null ? `${Math.round(price)}€` : "···"}
        </div>
    );
}

function MapController({
    geocodedCenter,
    positions,
    skipBoundsFit,
    onMoved,
    onCenterSet,
    onMapClick,
}: {
    geocodedCenter: [number, number] | null;
    positions: [number, number][];
    skipBoundsFit: boolean;
    onMoved: () => void;
    onCenterSet: () => void;
    onMapClick: () => void;
}) {
    const { map, isLoaded } = useMap();
    const hasGeocodedRef = useRef(false);
    const hasFittedRef = useRef(false);
    const isProgrammaticRef = useRef(false);
    const onMovedRef = useRef(onMoved);
    const onCenterSetRef = useRef(onCenterSet);
    const onMapClickRef = useRef(onMapClick);

    useEffect(() => {
        onMovedRef.current = onMoved;
    }, [onMoved]);

    useEffect(() => {
        onCenterSetRef.current = onCenterSet;
    }, [onCenterSet]);

    useEffect(() => {
        onMapClickRef.current = onMapClick;
    }, [onMapClick]);

    useEffect(() => {
        if (!map || !isLoaded) return;
        if (geocodedCenter === null) {
            hasGeocodedRef.current = false;
            return;
        }
        if (hasGeocodedRef.current) return;
        hasGeocodedRef.current = true;
        isProgrammaticRef.current = true;
        map.once("moveend", () => {
            isProgrammaticRef.current = false;
            onCenterSetRef.current();
        });
        map.flyTo({ center: [geocodedCenter[1], geocodedCenter[0]], zoom: 11 });
    }, [map, isLoaded, geocodedCenter]);

    useEffect(() => {
        if (skipBoundsFit || hasFittedRef.current || positions.length === 0 || !map || !isLoaded)
            return;
        hasFittedRef.current = true;
        isProgrammaticRef.current = true;
        const clearProgrammatic = () => {
            isProgrammaticRef.current = false;
        };
        if (positions.length === 1) {
            map.once("moveend", clearProgrammatic);
            map.flyTo({ center: [positions[0]![1], positions[0]![0]], zoom: 13 });
        } else {
            const lats = positions.map((p) => p[0]);
            const lngs = positions.map((p) => p[1]);
            map.once("moveend", clearProgrammatic);
            map.fitBounds(
                [
                    [Math.min(...lngs), Math.min(...lats)],
                    [Math.max(...lngs), Math.max(...lats)],
                ],
                { padding: 60 },
            );
        }
    }, [map, isLoaded, positions, skipBoundsFit]);

    useEffect(() => {
        if (!map || !isLoaded) return;
        const moveHandler = () => {
            if (!isProgrammaticRef.current) onMovedRef.current();
        };
        const clickHandler = () => onMapClickRef.current();
        map.on("dragend", moveHandler);
        map.on("zoomend", moveHandler);
        map.on("click", clickHandler);
        return () => {
            map.off("dragend", moveHandler);
            map.off("zoomend", moveHandler);
            map.off("click", clickHandler);
        };
    }, [map, isLoaded]);

    return null;
}

function ExploreMap({
    activities,
    highlightedId,
    onMarkerClick,
    onSearchArea,
    geocodedCenter,
    onDismissHighlight,
}: {
    activities: ActivityModel[];
    highlightedId: string | null;
    onMarkerClick: (id: string) => void;
    onSearchArea: (area: SearchArea) => void;
    geocodedCenter: [number, number] | null;
    onDismissHighlight: () => void;
}) {
    const t = useTranslations();
    const [hasMoved, setHasMoved] = useState(false);
    const mapRef = useRef<MapRef>(null);

    const validActivities = useMemo(
        () =>
            activities.filter(
                (e) =>
                    e.address !== null &&
                    e.address.latitude !== null &&
                    e.address.longitude !== null,
            ),
        [activities],
    );

    const positions = useMemo(
        () =>
            validActivities.map(
                (e) => [e.address!.latitude!, e.address!.longitude!] as [number, number],
            ),
        [validActivities],
    );

    function handleSearchInArea() {
        const map = mapRef.current;
        if (!map) return;
        const center = map.getCenter();
        const b = map.getBounds();
        const coords = { lat: center.lat, lng: center.lng };
        const bounds = {
            north: b.getNorth(),
            south: b.getSouth(),
            east: b.getEast(),
            west: b.getWest(),
        };
        const radius = Math.ceil(haversineKm(coords.lat, coords.lng, bounds.north, bounds.east));
        onSearchArea({ coords, bounds, radius });
        setHasMoved(false);
    }

    return (
        <div className="relative w-full h-full">
            <Map
                ref={mapRef}
                center={DEFAULT_CENTER}
                zoom={5}
                className="absolute inset-0 rounded-none"
            >
                <MapController
                    geocodedCenter={geocodedCenter}
                    positions={positions}
                    skipBoundsFit={geocodedCenter !== null}
                    onMoved={() => setHasMoved(true)}
                    onCenterSet={handleSearchInArea}
                    onMapClick={onDismissHighlight}
                />
                <MapControls position="top-right" showZoom showLocate className="hidden md:flex" />
                {validActivities.map((e, i) => (
                    <MapMarker
                        key={e.id}
                        longitude={positions[i]![1]}
                        latitude={positions[i]![0]}
                        onClick={(evt) => {
                            evt.stopPropagation();
                            onMarkerClick(e.id);
                        }}
                    >
                        <MarkerContent>
                            <PriceMarkerIcon
                                price={e.minPrice}
                                highlighted={highlightedId === e.id}
                                isProfessional={e.isProfessional}
                            />
                        </MarkerContent>
                    </MapMarker>
                ))}
            </Map>
            {hasMoved && (
                <div className="absolute top-2 start-1/2 -translate-x-1/2 z-50 pointer-events-none">
                    <Button
                        size="default"
                        onClick={handleSearchInArea}
                        className="pointer-events-auto"
                    >
                        <PointOnMap className="size-3.5" weight="Bold" />
                        {t("features.explore.searchThisArea")}
                    </Button>
                </div>
            )}
        </div>
    );
}

function EmptyResults({ onExpand, onModify }: { onExpand: () => void; onModify: () => void }) {
    const t = useTranslations();

    return (
        <div className="flex flex-col items-center justify-center gap-4 py-12 px-6 text-center">
            <div className="size-16 rounded-full bg-muted flex items-center justify-center">
                <MapPin className="size-7 text-muted-foreground" />
            </div>
            <div>
                <h3 className="font-bold text-base mb-1">
                    {t("features.explore.emptySearch.title")}
                </h3>
                <p className="text-sm text-muted-foreground">
                    {t("features.explore.emptySearch.description")}
                </p>
            </div>
            <div className="flex flex-col gap-2 w-full max-w-xs">
                <Button onClick={onExpand} className="rounded-full w-full">
                    {t("features.explore.expandArea")}
                </Button>
                <Button onClick={onModify} variant="outline" className="rounded-full w-full">
                    {t("features.explore.modifyCriteria")}
                </Button>
            </div>
        </div>
    );
}

function HighlightedHostOverlay({
    host,
    userDistanceMap,
    onClose,
}: {
    host: ActivityModel | null;
    userDistanceMap: Record<string, number | null>;
    onClose: () => void;
}) {
    return (
        <div
            className={cn(
                "fixed bottom-0 start-0 end-0 z-[51] transition-transform duration-300 ease-out will-change-transform",
                host ? "translate-y-0" : "translate-y-full pointer-events-none",
            )}
        >
            {host && (
                <MapDetailCard
                    host={host}
                    distanceOverride={userDistanceMap[host.id] ?? null}
                    onClose={onClose}
                />
            )}
        </div>
    );
}

function MapDetailCard({
    host,
    distanceOverride,
    onClose,
}: {
    host: ActivityModel;
    distanceOverride: number | null;
    onClose: () => void;
}) {
    const t = useTranslations();
    const { routes, router } = useNavigation();

    return (
        <div className="p-3" onClick={() => router.push(routes.HostDetail({ id: host.id }))}>
            <div className="bg-card rounded-3xl shadow-2xl p-2 overflow-hidden">
                <div className="relative">
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label={t("common.actions.close")}
                        className="absolute top-1.5 start-1.5 z-10 size-7 flex items-center justify-center rounded-full bg-card backdrop-blur-sm shadow-sm"
                    >
                        <X className="size-3.5 text-muted-foreground" />
                    </button>
                    <HostCard
                        host={host}
                        variant="horizontal"
                        distanceOverride={distanceOverride}
                    />
                </div>
            </div>
        </div>
    );
}

function stringParam(val: unknown): string {
    return typeof val === "string" ? val : "";
}

function expandSearchArea(
    searchArea: SearchArea | null,
    geocodedCenter: [number, number] | null,
): SearchArea | null {
    const coords =
        searchArea?.coords ??
        (geocodedCenter ? { lat: geocodedCenter[0], lng: geocodedCenter[1] } : null);
    if (!coords) return null;
    return { coords, bounds: null, radius: (searchArea?.radius ?? GEOCODE_DEFAULT_RADIUS) * 2 };
}

function filterHosts(
    activities: ActivityModel[],
    activeFilter: string,
    bounds: MapBounds | null,
): ActivityModel[] {
    let result = activities;
    if (bounds) {
        result = result.filter(
            (e) =>
                e.address !== null &&
                e.address.latitude !== null &&
                e.address.longitude !== null &&
                e.address.latitude >= bounds.south &&
                e.address.latitude <= bounds.north &&
                e.address.longitude >= bounds.west &&
                e.address.longitude <= bounds.east,
        );
    }
    if (activeFilter === "pro") return result.filter((e) => e.isProfessional);
    if (activeFilter === "particulier") return result.filter((e) => !e.isProfessional);
    if (activeFilter === "top-rated")
        return result.filter((e) => e.rating !== null && e.rating >= 4.5);
    return result;
}

function findHighlightedHost(hosts: ActivityModel[], id: string | null): ActivityModel | null {
    return hosts.find((e) => e.id === id) ?? null;
}

function buildUserDistanceMap(
    hosts: ActivityModel[],
    userLocation: { lat: number; lng: number } | null,
): Record<string, number | null> {
    const result: Record<string, number | null> = {};
    for (const host of hosts) {
        const lat = host.address?.latitude ?? null;
        const lng = host.address?.longitude ?? null;
        result[host.id] =
            userLocation !== null && lat !== null && lng !== null
                ? Math.round(haversineKm(userLocation.lat, userLocation.lng, lat, lng))
                : null;
    }
    return result;
}

function buildDateRangeText(dateFrom: string, dateTo: string, format: (d: Date) => string): string {
    return dateFrom && dateTo ? `${format(new Date(dateFrom))}–${format(new Date(dateTo))}` : "";
}

function resolveSearchParams(
    location: string,
    searchArea: SearchArea | null,
    geocodedCenter: [number, number] | null,
): {
    location: string | undefined;
    coords: SearchCoords | undefined;
    radius: number | undefined;
    bounds: MapBounds | null;
} {
    if (searchArea) {
        return {
            location: undefined,
            coords: searchArea.coords,
            radius: searchArea.radius,
            bounds: searchArea.bounds,
        };
    }
    if (geocodedCenter) {
        return {
            location: undefined,
            coords: { lat: geocodedCenter[0], lng: geocodedCenter[1] },
            radius: GEOCODE_DEFAULT_RADIUS,
            bounds: null,
        };
    }
    return { location, coords: undefined, radius: undefined, bounds: null };
}

function useSnapPoints() {
    const [snapMax] = useState<string>(() =>
        typeof window !== "undefined" ? `${window.innerHeight - 100}px` : "90vh",
    );
    const [snap, setSnap] = useState<number | string | null>(SNAP_MID);
    const snapPoints: (number | string)[] = [SNAP_MIN, SNAP_MID, snapMax];

    function handleSetSnap(value: number | string | null) {
        setSnap(value ?? SNAP_MIN);
    }

    function handleToggleSnap() {
        if (snap === SNAP_MIN || snap === snapMax) {
            setSnap(SNAP_MID);
        }
    }

    return { snap, snapMax, snapPoints, handleSetSnap, handleToggleSnap };
}

export default function ExploreResultsPage() {
    const t = useTranslations();
    const formatter = useFormatter();
    const router = useRouter();
    const { setBottomNavbarVisible } = useNavVisibility();
    const userLocation = useUserLocation();
    const { snap, snapMax, snapPoints, handleSetSnap, handleToggleSnap } = useSnapPoints();

    const [activeFilter, setActiveFilter] = useState("all");
    const [highlightedId, setHighlightedId] = useState<string | null>(null);
    const [searchArea, setSearchArea] = useState<SearchArea | null>(null);

    const { params } = useNavigation();
    const location = stringParam(params.location);
    const dateFrom = stringParam(params.dateFrom);
    const dateTo = stringParam(params.dateTo);

    const geocodedCenter = useGeocodeLocation(location);

    const petCounts: Record<string, number> = {};
    PET_TYPES.forEach((type) => {
        const val = params[type];
        if (typeof val === "string") {
            const count = parseInt(val, 10);
            if (count > 0) petCounts[type] = count;
        }
    });

    const searchParams = resolveSearchParams(location, searchArea, geocodedCenter);
    const { bounds } = searchParams;
    const { activities, isLoading } = useSearchResults({
        location: searchParams.location,
        coords: searchParams.coords,
        radius: searchParams.radius,
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

    const filteredHosts = filterHosts(activities, activeFilter, bounds);
    const highlightedHost = findHighlightedHost(filteredHosts, highlightedId);

    const userDistanceMap = buildUserDistanceMap(filteredHosts, userLocation);

    useEffect(() => {
        setBottomNavbarVisible(false);
        return () => setBottomNavbarVisible(true);
    }, [setBottomNavbarVisible]);

    const petSummary = Object.entries(petCounts)
        .filter(([, count]) => count > 0)
        .map(([type, count]) => {
            const label = t(`features.search.pets.${type}` as Parameters<typeof t>[0]);
            return `${count} ${label}`;
        })
        .join(", ");

    const dateOpts = { day: "numeric", month: "short" } as const;
    const dateRangeText = buildDateRangeText(dateFrom, dateTo, (d) =>
        formatter.dateTime(d, dateOpts),
    );

    const searchSummary = [
        searchParams.bounds !== null ? t("features.explore.mapArea") : location,
        dateRangeText,
        petSummary,
    ]
        .filter(Boolean)
        .join(" · ");

    function handleExpandArea() {
        const expanded = expandSearchArea(searchArea, geocodedCenter);
        if (expanded) setSearchArea(expanded);
    }

    function handleSearchAndClearBounds() {
        setSearchArea(null);
        handleSearch();
    }

    return (
        <div className="fixed inset-0 z-40 flex flex-col bg-card overflow-hidden">
            <div className="bg-card shadow-sm">
                <div className="shrink-0 bg-card px-3 py-2.5 pb-0 flex items-center gap-1">
                    <Button onClick={() => router.back()} variant="flat" size="icon-sm">
                        <ChevronLeft className="size-4" />
                    </Button>

                    <CompactSearchTrigger summary={searchSummary} onModify={openOverlay} />

                    <Button
                        variant="flat"
                        size="icon-sm"
                        aria-label={t("features.explore.filters")}
                    >
                        <SlidersHorizontal className="size-4" />
                    </Button>
                </div>

                <div className="shrink-0 py-2 shadow-sm">
                    <FilterChips activeFilter={activeFilter} onSelect={setActiveFilter} />
                </div>
            </div>

            <div className="flex-1 relative overflow-hidden">
                <div
                    className={cn(
                        "w-full h-full transition-all max-h-[100dvh]",
                        snap === SNAP_MID && !highlightedHost && "max-h-[50dvh] transition-all",
                    )}
                >
                    <ExploreMap
                        activities={filteredHosts}
                        highlightedId={highlightedId}
                        onMarkerClick={(id) =>
                            setHighlightedId((prev) => (prev === id ? null : id))
                        }
                        onSearchArea={setSearchArea}
                        geocodedCenter={geocodedCenter}
                        onDismissHighlight={() => setHighlightedId(null)}
                    />
                </div>

                <DrawerPrimitive.Root
                    snapPoints={snapPoints}
                    activeSnapPoint={snap}
                    setActiveSnapPoint={handleSetSnap}
                    modal={false}
                    dismissible={false}
                    open
                >
                    <DrawerPrimitive.Content
                        className={cn(
                            "fixed bottom-0 start-0 end-0 z-50 flex flex-col bg-card outline-none h-full shadow-2xl transition-[border-radius,opacity] duration-300",
                            highlightedHost && "opacity-0 pointer-events-none",
                            snap === snapMax
                                ? "rounded-none ring-0 pt-4 shadow-none"
                                : "rounded-t-3xl ring-1 ring-border/20",
                        )}
                        onClick={snap !== snapMax ? handleToggleSnap : undefined}
                    >
                        {snap !== snapMax && (
                            <button
                                className="mx-auto mt-3 mb-2 w-10 h-1 rounded-full bg-muted shrink-0"
                                aria-label={t("features.explore.adjustPanel")}
                            />
                        )}

                        <div
                            className={cn(
                                "flex items-center justify-center p-4 pt-2 shrink-0 font-bold text-lg transition-all",
                                snap === SNAP_MIN && "pt-0 text-base pb-2",
                                snap === snapMax && "pt-0",
                            )}
                        >
                            <DrawerPrimitive.Title asChild>
                                <h2>
                                    {isLoading ? (
                                        <span className="h-5 w-36 bg-muted animate-pulse rounded-full inline-block align-middle" />
                                    ) : (
                                        t("features.explore.hostsAvailable", {
                                            count: filteredHosts.length,
                                        })
                                    )}
                                </h2>
                            </DrawerPrimitive.Title>
                        </div>

                        <div
                            {...(snap !== snapMax ? { "data-vaul-no-drag": true } : {})}
                            className={cn(
                                "flex-1",
                                snap === SNAP_MIN ? "hidden" : "overflow-y-auto",
                            )}
                        >
                            {filteredHosts.length === 0 ? (
                                <EmptyResults onExpand={handleExpandArea} onModify={openOverlay} />
                            ) : (
                                <div className="flex flex-col gap-6">
                                    {filteredHosts.map((host) => (
                                        <div key={host.id}>
                                            <HostCard
                                                host={host}
                                                className="px-3 py-1"
                                                variant="horizontal"
                                                highlighted={highlightedId === host.id}
                                                distanceOverride={userDistanceMap[host.id] ?? null}
                                                onClick={() =>
                                                    setHighlightedId((prev) =>
                                                        prev === host.id ? null : host.id,
                                                    )
                                                }
                                            />
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </DrawerPrimitive.Content>
                </DrawerPrimitive.Root>
            </div>

            <HighlightedHostOverlay
                host={highlightedHost}
                userDistanceMap={userDistanceMap}
                onClose={() => setHighlightedId(null)}
            />

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
                    onSearch={handleSearchAndClearBounds}
                    onSetLocationSearchActive={setLocationSearchActive}
                />
            )}
        </div>
    );
}

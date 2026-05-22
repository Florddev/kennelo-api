"use client";

import { useEffect } from "react";
import { useTranslations } from "next-intl";
import { X, Navigation, Clock } from "lucide-react";
import type { DateRange } from "react-day-picker";

import { cn } from "@workspace/ui/lib/utils";
import { Button } from "@workspace/ui/components/button";
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from "@workspace/ui/components/collapsible";

import { DatesPanel } from "../panels/dates-panel";
import { PetsPanel } from "../panels/pets-panel";
import { MobileLocationFullscreen } from "./mobile-location-fullscreen";
import type { LocationSuggestion, PetCounts, PetType, RecentSearch } from "../../lib/types";
import { RECENT_SEARCHES } from "../../lib/constants";
import type { MobileCollapsible } from "../../hooks/use-mobile-search";
import { Magnifier } from "@solar-icons/react";

type MobileSearchOverlayProps = {
    activeCollapsible: MobileCollapsible;
    locationSearchActive: boolean;
    location: string;
    dateRange: DateRange | undefined;
    petCounts: PetCounts;
    totalPets: number;
    filteredSuggestions: LocationSuggestion[];
    dateDisplay: string | null;
    isLastStep: boolean;
    locationInputRef: React.RefObject<HTMLInputElement | null>;
    formatDate: (date: Date) => string;
    onClose: () => void;
    onToggleCollapsible: (panel: MobileCollapsible) => void;
    onSelectLocation: (name: string) => void;
    onSelectRecentSearch: (recent: RecentSearch) => void;
    onClearLocation: () => void;
    onChangeLocation: (value: string) => void;
    onSelectDateRange: (range: DateRange | undefined) => void;
    onAdjustPet: (type: PetType, delta: number) => void;
    onClearAll: () => void;
    onNext: () => void;
    onSearch: () => void;
    onSetLocationSearchActive: (active: boolean) => void;
};

function CollapsibleCard({
    label,
    value,
    placeholder,
    isOpen,
    onToggle,
    children,
}: {
    label: string;
    value?: string | null;
    placeholder: string;
    isOpen: boolean;
    onToggle: () => void;
    children: React.ReactNode;
}) {
    return (
        <Collapsible open={isOpen} onOpenChange={onToggle} className="transition-all">
            <div className="bg-card rounded-2xl overflow-hidden shadow-lg ring-1 ring-border/60">
                <CollapsibleTrigger className="w-full flex items-center justify-between p-4 text-start">
                    <div>
                        <div
                            className={cn(
                                "text-sm font-semibold transition-all",
                                isOpen && "text-xl font-bold text-primary",
                            )}
                        >
                            {label}
                        </div>
                    </div>
                    <div
                        className={cn(
                            "text-sm font-medium leading-tight",
                            value ? "text-foreground" : "text-muted-foreground",
                            isOpen && "hidden",
                        )}
                    >
                        {value || placeholder}
                    </div>
                </CollapsibleTrigger>
                <CollapsibleContent className="text-popover-foreground outline-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2">
                    {children}
                </CollapsibleContent>
            </div>
        </Collapsible>
    );
}

function LocationSuggestionRow({
    icon,
    iconClassName,
    className,
    title,
    subtitle,
    onClick,
}: {
    icon: React.ReactNode;
    iconClassName?: string;
    className?: string;
    title: string;
    subtitle: string;
    onClick: () => void;
}) {
    return (
        <button
            onClick={onClick}
            className={cn(
                "w-full flex items-center gap-3 px-4 py-3 hover:bg-muted/40 active:bg-muted/60 transition-colors text-start",
                className,
            )}
        >
            <div
                className={cn(
                    "size-10 rounded-xl flex items-center justify-center shrink-0",
                    iconClassName ?? "bg-muted",
                )}
            >
                {icon}
            </div>
            <div className="flex-1 min-w-0">
                <div className="text-sm font-semibold truncate">{title}</div>
                <div className="text-xs text-muted-foreground truncate">{subtitle}</div>
            </div>
        </button>
    );
}

export function MobileSearchOverlay({
    activeCollapsible,
    locationSearchActive,
    location,
    dateRange,
    petCounts,
    totalPets,
    filteredSuggestions,
    dateDisplay,
    isLastStep,
    locationInputRef,
    formatDate,
    onClose,
    onToggleCollapsible,
    onSelectLocation,
    onSelectRecentSearch,
    onClearLocation,
    onChangeLocation,
    onSelectDateRange,
    onAdjustPet,
    onClearAll,
    onNext,
    onSearch,
    onSetLocationSearchActive,
}: MobileSearchOverlayProps) {
    const t = useTranslations();

    useEffect(() => {
        document.body.style.overflow = "hidden";
        return () => {
            document.body.style.overflow = "";
        };
    }, []);

    return (
        <>
            <div
                data-slot="mobile-search-overlay"
                className="fixed inset-0 z-50 backdrop-blur-xs bg-card p-0"
            >
                <div
                    data-slot="mobile-search-overlay"
                    className="flex flex-col h-full max-h-dvh bg-muted/50 overflow-hidden shadow-lg"
                >
                    <div className="flex items-center bg-card justify-between px-4 pt-4 pb-3 border-b border-border shrink-0">
                        <h2 className="text-lg font-bold">{t("features.search.mobile.title")}</h2>
                        <button
                            onClick={onClose}
                            className="size-9 rounded-full bg-muted flex items-center justify-center hover:bg-muted/70 transition-colors"
                        >
                            <X className="size-4" />
                        </button>
                    </div>

                    <div className="flex-1 overflow-hidden h-full max-h-full p-4 flex flex-col gap-2 bg-muted/30">
                        <CollapsibleCard
                            label={t("features.search.mobile.where")}
                            value={location || null}
                            placeholder={t("features.search.destination")}
                            isOpen={activeCollapsible === "location"}
                            onToggle={() => onToggleCollapsible("location")}
                        >
                            <div className="px-4">
                                <Button
                                    onClick={() => onSetLocationSearchActive(true)}
                                    className="w-full text-start gap-2 h-auto p-3 rounded-sm"
                                    variant="flat"
                                >
                                    <Magnifier className="size-4 shrink-0" />
                                    <span
                                        className={cn(
                                            "flex-1 text-sm truncate",
                                            location
                                                ? "text-foreground font-medium"
                                                : "text-muted-foreground",
                                        )}
                                    >
                                        {location ||
                                            t("common.placeholders.search-for-destination")}
                                    </span>
                                </Button>
                            </div>

                            <div className="px-4 pt-2">
                                <span className="text-sm font-medium text-muted-foreground">
                                    {t("features.search.recent-searches")}
                                </span>
                            </div>

                            <LocationSuggestionRow
                                onClick={() => onSelectLocation(t("features.search.nearby"))}
                                iconClassName="bg-secondary/20"
                                icon={<Navigation className="size-4 text-secondary-foreground" />}
                                title={t("features.search.nearby")}
                                subtitle={t("features.search.nearby-description")}
                            />

                            {RECENT_SEARCHES.length > 0 && (
                                <>
                                    <div className="px-4 pt-1">
                                        <span className="text-sm font-medium text-muted-foreground">
                                            {t("features.search.recent-searches")}
                                        </span>
                                    </div>
                                    <div className="flex flex-col gap-2 py-4 pt-3">
                                        {RECENT_SEARCHES.map((recent) => (
                                            <LocationSuggestionRow
                                                key={recent.id}
                                                className="py-0"
                                                onClick={() => onSelectRecentSearch(recent)}
                                                icon={
                                                    <Clock className="size-4 text-muted-foreground" />
                                                }
                                                title={
                                                    recent.location.split(",")[0] ?? recent.location
                                                }
                                                subtitle={`${formatDate(recent.dateFrom)} – ${formatDate(recent.dateTo)} · ${t("features.search.pets.count", { count: recent.petCount })}`}
                                            />
                                        ))}
                                    </div>
                                </>
                            )}
                        </CollapsibleCard>

                        <CollapsibleCard
                            label={t("features.search.mobile.when")}
                            value={dateDisplay}
                            placeholder={t("features.search.when")}
                            isOpen={activeCollapsible === "dates"}
                            onToggle={() => onToggleCollapsible("dates")}
                        >
                            <DatesPanel
                                dateRange={dateRange}
                                onSelect={onSelectDateRange}
                                numberOfMonths={1}
                                className="static w-full shadow-none ring-0 rounded-none translate-x-0 start-0 p-0 m-0"
                                calendarClassName="pt-0"
                            />
                        </CollapsibleCard>

                        <CollapsibleCard
                            label={t("features.search.mobile.who")}
                            value={
                                totalPets > 0
                                    ? t("features.search.pets.count", { count: totalPets })
                                    : null
                            }
                            placeholder={t("features.search.pets.add")}
                            isOpen={activeCollapsible === "pets"}
                            onToggle={() => onToggleCollapsible("pets")}
                        >
                            <PetsPanel
                                petCounts={petCounts}
                                onAdjust={onAdjustPet}
                                className="static w-full shadow-none ring-0 rounded-none end-auto m-0"
                            />
                        </CollapsibleCard>
                    </div>

                    <div className="px-4 py-3 shrink-0 flex items-center justify-between gap-4">
                        <button
                            onClick={onClearAll}
                            className="text-sm font-medium text-muted-foreground underline underline-offset-2 shrink-0 hover:text-foreground transition-colors"
                        >
                            {t("features.search.mobile.clear-all")}
                        </button>
                        <Button
                            onClick={isLastStep ? onSearch : onNext}
                            className="rounded-full h-12 px-7 text-[15px] font-semibold"
                        >
                            {isLastStep
                                ? t("features.search.cta")
                                : t("features.search.mobile.next")}
                        </Button>
                    </div>
                </div>
            </div>

            {locationSearchActive && (
                <MobileLocationFullscreen
                    location={location}
                    filteredSuggestions={filteredSuggestions}
                    locationInputRef={locationInputRef}
                    onSelect={onSelectLocation}
                    onClear={onClearLocation}
                    onChange={onChangeLocation}
                    onBack={() => onSetLocationSearchActive(false)}
                    formatDate={formatDate}
                />
            )}
        </>
    );
}

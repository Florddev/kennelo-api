"use client";

import { useState, useMemo, useRef, useEffect } from "react";
import { useRouter } from "next/navigation";
import { useLocale } from "next-intl";
import { X, Navigation, MapPin, Clock } from "lucide-react";
import type { DateRange } from "react-day-picker";

import { cn } from "@workspace/ui/lib/utils";
import { Button } from "@workspace/ui/components/button";
import { Calendar } from "@workspace/ui/components/calendar";

import { LOCATION_SUGGESTIONS, RECENT_SEARCHES } from "@/features/search/lib/constants";

const MODAL_PET_TYPES = [
    { id: "dog", label: "Chien", icon: "/illustrations/pets/dog.svg" },
    { id: "cat", label: "Chat", icon: "/illustrations/pets/cat.svg" },
    { id: "bird", label: "Oiseau", icon: "/illustrations/pets/bird.svg" },
    { id: "reptile", label: "Reptile", icon: "/illustrations/pets/reptile.svg" },
    { id: "rodent", label: "Rongeur", icon: null },
    { id: "horse", label: "Équidé", icon: null },
    { id: "other", label: "Autre", icon: null },
] as const;

type ModalPetType = (typeof MODAL_PET_TYPES)[number]["id"];

type SearchModalProps = {
    isOpen: boolean;
    onClose: () => void;
    initialLocation?: string;
    initialDateRange?: DateRange;
    initialPetTypes?: ModalPetType[];
};

type SearchModalContentProps = Omit<SearchModalProps, "isOpen">;

export function SearchModal({ isOpen, ...props }: SearchModalProps) {
    if (!isOpen) return null;
    return <SearchModalContent {...props} />;
}

function SearchModalContent({
    onClose,
    initialLocation = "",
    initialDateRange,
    initialPetTypes = [],
}: SearchModalContentProps) {
    const router = useRouter();
    const locale = useLocale();
    const locationInputRef = useRef<HTMLInputElement>(null);

    const [location, setLocation] = useState(initialLocation);
    const [dateRange, setDateRange] = useState<DateRange | undefined>(initialDateRange);
    const [selectedPets, setSelectedPets] = useState<ModalPetType[]>(initialPetTypes);
    const [showSuggestions, setShowSuggestions] = useState(true);

    const filteredSuggestions = useMemo(() => {
        if (!location.trim()) return [];
        const q = location.toLowerCase();
        return LOCATION_SUGGESTIONS.filter((s) => s.name.toLowerCase().includes(q));
    }, [location]);

    useEffect(() => {
        const timer = setTimeout(() => locationInputRef.current?.focus(), 100);
        return () => clearTimeout(timer);
    }, []);

    function togglePet(pet: ModalPetType) {
        setSelectedPets((prev) =>
            prev.includes(pet) ? prev.filter((p) => p !== pet) : [...prev, pet],
        );
    }

    function handleSelectLocation(name: string) {
        setLocation(name);
        setShowSuggestions(false);
    }

    function handleClear() {
        setLocation("");
        setDateRange(undefined);
        setSelectedPets([]);
        setShowSuggestions(true);
    }

    function formatDate(date: Date) {
        return new Intl.DateTimeFormat(locale, { month: "short", day: "numeric" }).format(date);
    }

    function getDateLabel() {
        if (!dateRange?.from) return "Quand ?";
        if (!dateRange.to) return formatDate(dateRange.from);
        return `${formatDate(dateRange.from)} – ${formatDate(dateRange.to)}`;
    }

    function handleSubmit() {
        const params = new URLSearchParams();
        if (location) params.set("location", location);
        if (dateRange?.from) params.set("dateFrom", dateRange.from.toISOString().slice(0, 10));
        if (dateRange?.to) params.set("dateTo", dateRange.to.toISOString().slice(0, 10));
        if (selectedPets.length) params.set("pets", selectedPets.join(","));

        onClose();
        router.push(`/${locale}/explore/results?${params.toString()}`);
    }

    const hasResults = 23;

    return (
        <div data-slot="search-modal" className="fixed inset-0 z-50 bg-background flex flex-col">
            <div className="flex items-center justify-between px-4 pt-4 pb-3 border-b border-border shrink-0">
                <h2 className="text-lg font-bold">Rechercher</h2>
                <button
                    onClick={onClose}
                    className="size-9 rounded-full bg-muted flex items-center justify-center hover:bg-muted/70 transition-colors"
                >
                    <X className="size-4" />
                </button>
            </div>

            <div className="flex-1 overflow-y-auto">
                <div className="p-4 flex flex-col gap-4">
                    <div className="rounded-2xl border border-border overflow-hidden">
                        <div className="px-4 pt-3 pb-1">
                            <div className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mb-1.5">
                                Lieu
                            </div>
                            <div className="flex items-center gap-2">
                                {location ? (
                                    <span className="size-2 rounded-full bg-secondary shrink-0" />
                                ) : (
                                    <MapPin className="size-3.5 text-muted-foreground shrink-0" />
                                )}
                                <input
                                    ref={locationInputRef}
                                    value={location}
                                    onChange={(e) => {
                                        setLocation(e.target.value);
                                        setShowSuggestions(true);
                                    }}
                                    onFocus={() => setShowSuggestions(true)}
                                    placeholder="Carhaix-Plouguer"
                                    className="flex-1 bg-transparent outline-none text-base font-medium placeholder:text-muted-foreground"
                                />
                                {location && (
                                    <button
                                        onClick={() => {
                                            setLocation("");
                                            setShowSuggestions(true);
                                            locationInputRef.current?.focus();
                                        }}
                                        className="size-5 rounded-full bg-muted-foreground/20 flex items-center justify-center shrink-0"
                                    >
                                        <X className="size-3" />
                                    </button>
                                )}
                            </div>
                        </div>

                        {showSuggestions && (
                            <div className="border-t border-border/50 py-1">
                                <button
                                    onClick={() => handleSelectLocation("Autour de moi")}
                                    className="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-muted/50 transition-colors"
                                >
                                    <div className="size-9 rounded-xl bg-muted flex items-center justify-center shrink-0">
                                        <Navigation className="size-4 text-muted-foreground" />
                                    </div>
                                    <div className="flex flex-col text-start">
                                        <span className="text-sm font-semibold">Autour de moi</span>
                                        <span className="text-xs text-muted-foreground">
                                            Position actuelle
                                        </span>
                                    </div>
                                </button>

                                {filteredSuggestions.length > 0
                                    ? filteredSuggestions.map((s) => (
                                          <button
                                              key={s.id}
                                              onClick={() => handleSelectLocation(s.name)}
                                              className="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-muted/50 transition-colors"
                                          >
                                              <div className="size-9 rounded-xl bg-muted flex items-center justify-center shrink-0">
                                                  <Clock className="size-4 text-muted-foreground" />
                                              </div>
                                              <div className="flex flex-col text-start">
                                                  <span className="text-sm font-semibold">
                                                      {s.name.split(",")[0]}
                                                  </span>
                                                  <span className="text-xs text-muted-foreground">
                                                      {s.name
                                                          .split(",")
                                                          .slice(1)
                                                          .join(",")
                                                          .trim() || s.type}
                                                  </span>
                                              </div>
                                          </button>
                                      ))
                                    : RECENT_SEARCHES.map((r) => (
                                          <button
                                              key={r.id}
                                              onClick={() => handleSelectLocation(r.location)}
                                              className="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-muted/50 transition-colors"
                                          >
                                              <div className="size-9 rounded-xl bg-muted flex items-center justify-center shrink-0">
                                                  <Clock className="size-4 text-muted-foreground" />
                                              </div>
                                              <div className="flex flex-col text-start">
                                                  <span className="text-sm font-semibold">
                                                      {r.location.split(",")[0]}
                                                  </span>
                                                  <span className="text-xs text-muted-foreground">
                                                      Récent
                                                  </span>
                                              </div>
                                          </button>
                                      ))}
                            </div>
                        )}
                    </div>

                    <div className="rounded-2xl border border-border overflow-hidden">
                        <div className="px-4 py-3 flex items-center justify-between">
                            <div>
                                <div className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mb-0.5">
                                    Dates
                                </div>
                                <div
                                    className={cn(
                                        "text-base font-medium",
                                        dateRange?.from
                                            ? "text-foreground"
                                            : "text-muted-foreground",
                                    )}
                                >
                                    {getDateLabel()}
                                </div>
                            </div>
                            {!dateRange && (
                                <button className="text-xs text-muted-foreground underline underline-offset-2">
                                    Je ne sais pas encore
                                </button>
                            )}
                        </div>
                        <div className="border-t border-border/50 px-2 pb-2">
                            <Calendar
                                mode="range"
                                selected={dateRange}
                                onSelect={setDateRange}
                                numberOfMonths={1}
                                disabled={{ before: new Date() }}
                                className="w-full bg-background"
                            />
                        </div>
                    </div>

                    <div className="rounded-2xl border border-border overflow-hidden">
                        <div className="px-4 pt-3 pb-3">
                            <div className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mb-3">
                                Animal · Sélection multiple
                            </div>
                            <div className="grid grid-cols-4 gap-2">
                                {MODAL_PET_TYPES.map((pet) => {
                                    const active = selectedPets.includes(pet.id);
                                    return (
                                        <button
                                            key={pet.id}
                                            onClick={() => togglePet(pet.id)}
                                            className={cn(
                                                "flex flex-col items-center gap-1.5 py-2.5 px-1 rounded-2xl border transition-all",
                                                active
                                                    ? "border-secondary bg-secondary/10 text-secondary"
                                                    : "border-border bg-background text-foreground hover:border-muted-foreground/40",
                                            )}
                                        >
                                            <div
                                                className={cn(
                                                    "size-10 rounded-full border-2 flex items-center justify-center text-sm font-bold",
                                                    active
                                                        ? "border-secondary text-secondary"
                                                        : "border-border text-muted-foreground",
                                                )}
                                            >
                                                {pet.label.slice(0, 2)}
                                            </div>
                                            <span className="text-xs font-medium">{pet.label}</span>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="px-4 py-4 border-t border-border shrink-0 flex items-center gap-4 bg-background">
                <button
                    onClick={handleClear}
                    className="text-sm text-muted-foreground underline underline-offset-2 shrink-0"
                >
                    Effacer
                </button>
                <Button onClick={handleSubmit} className="flex-1 gap-2 rounded-full h-12">
                    <span className="text-[15px] font-semibold">
                        Voir les {hasResults} hôtes disponibles
                    </span>
                </Button>
            </div>
        </div>
    );
}

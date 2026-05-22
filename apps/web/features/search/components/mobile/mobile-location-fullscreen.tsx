"use client";

import { useTranslations } from "next-intl";
import { ArrowLeft, X } from "lucide-react";

import { LocationPanel } from "../panels/location-panel";
import type { LocationSuggestion } from "../../lib/types";
import { Input } from "@workspace/ui/components/input";

type MobileLocationFullscreenProps = {
    location: string;
    filteredSuggestions: LocationSuggestion[];
    locationInputRef: React.RefObject<HTMLInputElement | null>;
    onSelect: (name: string) => void;
    onClear: () => void;
    onChange: (value: string) => void;
    onBack: () => void;
    formatDate: (date: Date) => string;
};

export function MobileLocationFullscreen({
    location,
    filteredSuggestions,
    locationInputRef,
    onSelect,
    onClear,
    onChange,
    onBack,
    formatDate,
}: MobileLocationFullscreenProps) {
    const t = useTranslations();

    return (
        <div
            data-slot="mobile-location-fullscreen"
            className="fixed inset-0 z-[51] bg-background flex flex-col"
        >
            <div className="flex items-center gap-2 px-4 pt-4 pb-3 border-b border-border shrink-0">
                <button
                    onClick={onBack}
                    className="size-9 rounded-full bg-muted flex items-center justify-center hover:bg-muted/70 transition-colors shrink-0"
                >
                    <ArrowLeft className="size-4" />
                </button>
                <div className="flex-1 flex items-center gap-2 bg-muted rounded-2xl px-2 focus-within:ring-2 focus-within:ring-primary">
                    <div className="px-1 w-full">
                        <Input
                            ref={locationInputRef}
                            value={location}
                            onChange={(e) => onChange(e.target.value)}
                            placeholder={t("common.placeholders.search-for-a-pension")}
                            className="flex-1 bg-transparent outline-none text-sm font-medium placeholder:text-muted-foreground border-none p-0 focus:ring-0 focus-visible:ring-0 border-1"
                            autoFocus
                        />
                    </div>
                    {location && (
                        <button
                            onPointerDown={(e) => {
                                e.preventDefault();
                                onClear();
                            }}
                            className="size-5 rounded-full bg-foreground/15 hover:bg-foreground/25 flex items-center justify-center transition-colors shrink-0"
                        >
                            <X className="size-3" />
                        </button>
                    )}
                </div>
            </div>

            <div className="flex-1 overflow-y-auto">
                <LocationPanel
                    location={location}
                    filteredSuggestions={filteredSuggestions}
                    onSelect={onSelect}
                    formatDate={formatDate}
                    className="static w-full shadow-none ring-0 rounded-none py-3 max-h-none"
                />
            </div>
        </div>
    );
}

"use client";

import { useTranslations } from "next-intl";
import { MapPin, Navigation } from "lucide-react";

import { cn } from "@workspace/ui/lib/utils";

import type { LocationSuggestion, RecentSearch } from "../../lib/types";
import { RECENT_SEARCHES } from "../../lib/constants";

type LocationPanelProps = {
    location: string;
    filteredSuggestions: LocationSuggestion[];
    onSelect: (suggestion: LocationSuggestion) => void;
    onSelectRecent: (name: string) => void;
    formatDate: (date: Date) => string;
    className?: string;
};

function SectionHeader({ label }: { label: string }) {
    return (
        <div className="border-s-2 border-secondary ps-2.5 mb-2">
            <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                {label}
            </span>
        </div>
    );
}

function SuggestionItem({
    icon,
    iconClassName,
    title,
    subtitle,
    onClick,
}: {
    icon: React.ReactNode;
    iconClassName?: string;
    title: React.ReactNode;
    subtitle: React.ReactNode;
    onClick: () => void;
}) {
    return (
        <button
            onClick={onClick}
            className="w-full flex items-center gap-3 px-2 py-2.5 rounded-xl hover:bg-secondary/10 transition-colors text-start"
        >
            <div
                className={cn(
                    "size-11 rounded-xl flex items-center justify-center shrink-0",
                    iconClassName ?? "bg-muted",
                )}
            >
                {icon}
            </div>
            <div className="flex-1 min-w-0">
                <div className="text-sm font-semibold truncate">{title}</div>
                <div className="text-xs text-muted-foreground">{subtitle}</div>
            </div>
        </button>
    );
}

function RecentSearchItem({
    recent,
    onSelect,
    formatDate,
    petCountLabel,
}: {
    recent: RecentSearch;
    onSelect: () => void;
    formatDate: (d: Date) => string;
    petCountLabel: string;
}) {
    return (
        <SuggestionItem
            onClick={onSelect}
            icon={<MapPin size={17} className="text-muted-foreground" />}
            title={recent.location}
            subtitle={`${formatDate(recent.dateFrom)} – ${formatDate(recent.dateTo)} · ${petCountLabel}`}
        />
    );
}

export function LocationPanel({
    location,
    filteredSuggestions,
    onSelect,
    onSelectRecent,
    formatDate,
    className,
}: LocationPanelProps) {
    const t = useTranslations();
    const isSearching = location.trim() !== "";

    return (
        <div
            data-slot="search-bar-location-panel"
            className={cn(
                "absolute z-50 top-full mt-3 start-0 w-96 bg-card rounded-2xl shadow-2xl ring-1 ring-border py-4 overflow-auto max-h-128",
                className,
            )}
        >
            {isSearching ? (
                <div className="px-3">
                    {filteredSuggestions.length === 0 ? (
                        <div className="px-4 py-6 text-sm text-muted-foreground text-center">
                            {t("common.placeholders.search-for-a-pension")}
                        </div>
                    ) : (
                        filteredSuggestions.map((suggestion) => (
                            <SuggestionItem
                                key={suggestion.id}
                                onClick={() => onSelect(suggestion)}
                                icon={<MapPin size={17} className="text-muted-foreground" />}
                                title={suggestion.name}
                                subtitle={suggestion.getSubtitle()}
                            />
                        ))
                    )}
                </div>
            ) : (
                <>
                    {RECENT_SEARCHES.length > 0 && (
                        <div className="px-4 pb-2">
                            <SectionHeader label={t("features.search.recent-searches")} />
                            {RECENT_SEARCHES.map((recent) => (
                                <RecentSearchItem
                                    key={recent.id}
                                    recent={recent}
                                    onSelect={() => onSelectRecent(recent.location)}
                                    formatDate={formatDate}
                                    petCountLabel={t("features.search.pets.count", {
                                        count: recent.petCount,
                                    })}
                                />
                            ))}
                        </div>
                    )}

                    {RECENT_SEARCHES.length > 0 && <div className="h-px bg-border mx-4 my-2" />}

                    <div className="px-4">
                        <SectionHeader label={t("features.search.suggestions")} />
                        <SuggestionItem
                            onClick={() => onSelectRecent(t("features.search.nearby"))}
                            iconClassName="bg-secondary/20"
                            icon={<Navigation size={17} className="text-secondary-foreground" />}
                            title={t("features.search.nearby")}
                            subtitle={t("features.search.nearby-description")}
                        />
                    </div>
                </>
            )}
        </div>
    );
}

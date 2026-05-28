"use client";

import { useEffect } from "react";
import { useTranslations } from "next-intl";
import { AlertCircle } from "lucide-react";
import { useAuth } from "@/features/auth";
import { ExploreSection } from "@/features/explore/components/explore-section";
import { LocationPrompt } from "@/features/explore/components/location-prompt";
import { LocationProvider, useLocation } from "@/features/explore/context/location-context";
import { useExploreEstablishments } from "@/features/explore/hooks/use-explore-establishments";
import SearchBar from "@/features/search/components/search-bar";
import MobileSearch from "@/features/search/components/mobile/mobile-search";
import { Button } from "@workspace/ui/components/button";
import { Bell } from "@solar-icons/react";

function SectionSkeleton() {
    return (
        <div className="flex flex-col gap-3">
            <div className="px-4 flex items-center justify-between">
                <div className="h-6 w-40 rounded-lg bg-muted animate-pulse" />
                <div className="h-4 w-14 rounded-lg bg-muted animate-pulse" />
            </div>
            <div className="flex gap-3 px-4 overflow-hidden">
                {[1, 2, 3].map((i) => (
                    <div key={i} className="shrink-0 w-44 flex flex-col gap-2">
                        <div className="h-32 w-full rounded-2xl bg-muted animate-pulse" />
                        <div className="h-4 w-28 rounded bg-muted animate-pulse" />
                        <div className="h-3 w-20 rounded bg-muted animate-pulse" />
                    </div>
                ))}
            </div>
        </div>
    );
}

function ExploreContent() {
    const t = useTranslations();
    const { user, isAuthenticated } = useAuth();
    const { coords, isDismissed, setCoords } = useLocation();
    const { sections, isLoading, error, retry } = useExploreEstablishments();

    useEffect(() => {
        if (isAuthenticated && user?.address?.latitude && user.address.longitude && !coords) {
            setCoords({ lat: user.address.latitude, lng: user.address.longitude });
        }
    }, [isAuthenticated, user, coords, setCoords]);

    const showLocationPrompt = !coords && !isDismissed;

    function renderBody() {
        if (isLoading) {
            return (
                <>
                    <SectionSkeleton />
                    <SectionSkeleton />
                    <SectionSkeleton />
                </>
            );
        }

        if (error) {
            return (
                <div className="mx-4 flex flex-col items-center gap-3 rounded-2xl bg-muted/40 px-6 py-8 text-center">
                    <AlertCircle className="size-8 text-muted-foreground" />
                    <div>
                        <p className="font-semibold text-sm">{t("features.explore.error.title")}</p>
                        <p className="text-xs text-muted-foreground mt-1">
                            {t("features.explore.error.description")}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={retry}
                        className="rounded-full bg-foreground text-background text-sm font-semibold px-5 py-2 hover:bg-foreground/90 transition-colors"
                    >
                        {t("features.explore.retry")}
                    </button>
                </div>
            );
        }

        if (sections.length === 0) {
            return (
                <div className="mx-4 flex flex-col items-center gap-2 rounded-2xl bg-muted/40 px-6 py-8 text-center">
                    <p className="font-semibold text-sm">{t("features.explore.empty.title")}</p>
                    <p className="text-xs text-muted-foreground">
                        {t("features.explore.empty.description")}
                    </p>
                </div>
            );
        }

        return sections.map((section) => <ExploreSection key={section.id} section={section} />);
    }

    return (
        <div className="flex flex-col bg-card min-h-full">
            <div className="px-4 pt-4 pb-3">
                <div className="flex flex-col gap-3">
                    <div className="flex justify-between items-center">
                        <div className="flex flex-col">
                            <h2 className="text-xl font-semibold">
                                Hey, {user?.firstName ?? "there"}
                            </h2>
                            <p className="text-xs text-muted-foreground">
                                Explore new host for your next booking
                            </p>
                        </div>
                        <Button variant="flat" size="icon-sm">
                            <Bell className="size-4" />
                        </Button>
                    </div>

                    <div className="hidden md:block">
                        <SearchBar />
                    </div>
                    <div className="md:hidden">
                        <MobileSearch />
                    </div>
                </div>
            </div>

            {showLocationPrompt && <LocationPrompt className="mb-4" />}

            <div className="flex flex-col gap-6 pb-8 pt-2">{renderBody()}</div>
        </div>
    );
}

export default function ExplorePage() {
    return (
        <LocationProvider>
            <ExploreContent />
        </LocationProvider>
    );
}

"use client";

import { useEffect } from "react";
import Image from "next/image";
import { useTranslations } from "next-intl";
import { useAuth } from "@/features/auth";
import { UserAvatar } from "@/features/auth/components/user-avatar";
import { SearchTrigger } from "@/features/explore/components/search-trigger";
import { SearchModal } from "@/features/explore/components/search-modal";
import { ExploreSection } from "@/features/explore/components/explore-section";
import { LocationPrompt } from "@/features/explore/components/location-prompt";
import { LocationProvider, useLocation } from "@/features/explore/context/location-context";
import { useExploreEstablishments } from "@/features/explore/hooks/use-explore-establishments";
import { useState } from "react";
import { AlertCircle } from "lucide-react";

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
    const [isModalOpen, setIsModalOpen] = useState(false);

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
        <div className="flex flex-col bg-background min-h-full">
            <header className="sticky top-0 z-10 bg-background/90 backdrop-blur-sm border-b border-border/40 px-4 py-3 flex items-center justify-between">
                <Image
                    src="/logo_font.svg"
                    height={24}
                    width={80}
                    alt="Kennelo"
                    className="h-6 w-auto"
                />
                <button className="size-9 rounded-full bg-muted flex items-center justify-center">
                    {isAuthenticated ? (
                        <UserAvatar user={user} className="size-8" />
                    ) : (
                        <span className="text-sm font-semibold text-muted-foreground">P</span>
                    )}
                </button>
            </header>

            <div className="px-4 pt-4 pb-3">
                <SearchTrigger onClick={() => setIsModalOpen(true)} />
            </div>

            {showLocationPrompt && <LocationPrompt className="mb-4" />}

            <div className="flex flex-col gap-8 pb-8 pt-2">{renderBody()}</div>

            <SearchModal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} />
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

"use client";

import { useState, useEffect, useRef, useCallback } from "react";
import { useTranslations } from "next-intl";
import { cn } from "@workspace/ui/lib/utils";
import { getExploreSection } from "@workspace/modules/establishments";
import type { EstablishmentModel, ExploreSectionModel } from "@workspace/modules/establishments";
import { useLocation } from "@/features/explore/context/location-context";
import { HostCard } from "./host-card";
import { Drawer, DrawerContent, DrawerHeader, DrawerTitle } from "@workspace/ui/components/drawer";

type ExploreSectionProps = {
    section: ExploreSectionModel;
    className?: string;
};

function SectionOverlay({
    section,
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    onClose,
}: {
    section: ExploreSectionModel;
    onClose: () => void;
}) {
    const t = useTranslations();
    const { coords } = useLocation();
    const [establishments, setEstablishments] = useState<EstablishmentModel[]>(
        section.establishments,
    );
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(section.hasMore);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const sentinelRef = useRef<HTMLDivElement>(null);

    const loadMore = useCallback(async () => {
        if (!hasMore || isLoadingMore) return;
        setIsLoadingMore(true);
        const nextPage = page + 1;
        const result = await getExploreSection(section.id, nextPage, coords ?? undefined);
        setEstablishments((prev) => [...prev, ...result.establishments]);
        setHasMore(result.meta.hasMore);
        setPage(nextPage);
        setIsLoadingMore(false);
    }, [hasMore, isLoadingMore, page, section.id, coords]);

    useEffect(() => {
        const sentinel = sentinelRef.current;
        if (!sentinel) return;

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0]?.isIntersecting) {
                    loadMore();
                }
            },
            { threshold: 0.1 },
        );

        observer.observe(sentinel);
        return () => observer.disconnect();
    }, [loadMore]);

    return (
        <div data-slot="section-overlay" className="flex flex-col h-full">
            <div className="flex-1 overflow-y-auto px-4 py-3 flex flex-col gap-2">
                {establishments.map((host) => (
                    <HostCard
                        key={host.id}
                        host={host}
                        variant="horizontal"
                        className="p-2 shadow-sm"
                    />
                ))}
                {hasMore && (
                    <div ref={sentinelRef} className="flex items-center justify-center py-4">
                        {isLoadingMore ? (
                            <span className="text-xs text-muted-foreground">
                                {t("features.explore.overlay.loading")}
                            </span>
                        ) : (
                            <span className="text-xs text-muted-foreground">
                                {t("features.explore.overlay.loadMore")}
                            </span>
                        )}
                    </div>
                )}
                {!hasMore && establishments.length > 0 && (
                    <p className="text-center text-xs text-muted-foreground py-4">
                        {t("features.explore.overlay.noMore")}
                    </p>
                )}
            </div>
        </div>
    );
}

export function ExploreSection({ section, className }: ExploreSectionProps) {
    const t = useTranslations();
    const [isOverlayOpen, setIsOverlayOpen] = useState(false);

    return (
        <>
            <section data-slot="explore-section" className={cn("flex flex-col", className)}>
                <div className="flex items-center justify-between px-4">
                    <h2 className="text-lg font-bold">
                        {t(`features.explore.sections.${section.id}`)}
                    </h2>
                    {section.hasMore && (
                        <button
                            type="button"
                            onClick={() => setIsOverlayOpen(true)}
                            className="text-sm text-foreground underline underline-offset-2 hover:text-muted-foreground transition-colors"
                        >
                            {t("features.explore.seeMore")}
                        </button>
                    )}
                </div>
                <div className="flex gap-4 overflow-x-auto scrollbar-none px-4 py-2.5">
                    {section.establishments.map((host) => (
                        <HostCard key={host.id} host={host} variant="vertical" />
                    ))}
                </div>
            </section>

            <Drawer open={isOverlayOpen} onOpenChange={setIsOverlayOpen}>
                <DrawerContent className="p-0">
                    <DrawerHeader>
                        <DrawerTitle>{t(`features.explore.sections.${section.id}`)}</DrawerTitle>
                    </DrawerHeader>
                    <div className="no-scrollbar overflow-y-auto p-0">
                        <SectionOverlay section={section} onClose={() => setIsOverlayOpen(false)} />
                    </div>
                </DrawerContent>
            </Drawer>
        </>
    );
}

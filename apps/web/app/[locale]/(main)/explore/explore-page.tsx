"use client";

import { useTranslations } from "next-intl";
import { ListFilter } from "lucide-react";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { KCompass } from "@workspace/ui/icons";

import { useNavigation } from "@/hooks/use-navigation";
import { EstablishmentCard, useExploreEstablishments } from "@/features/explore";

export default function ExplorePage() {
    const t = useTranslations();
    const { routes } = useNavigation();
    const { establishments, isLoading } = useExploreEstablishments();
    const count = establishments.length;

    return (
        <div className="flex h-full flex-col">
            <header className="flex items-center justify-between px-3 py-3">
                <div className="flex items-center gap-2">
                    <div className="flex size-12 items-center justify-center rounded-full bg-secondary">
                        <KCompass
                            size={28}
                            primary="text-secondary-foreground"
                            secondary="text-secondary-foreground"
                        />
                    </div>
                    <h1 className="text-2xl font-semibold text-foreground">
                        {t("features.explore.title")}
                    </h1>
                </div>
                <button
                    type="button"
                    aria-label={t("features.explore.filters")}
                    className="flex size-10 items-center justify-center rounded-full border border-border text-foreground"
                >
                    <ListFilter className="size-4" />
                </button>
            </header>

            <div className="flex-1 overflow-y-auto px-3 pb-6">
                {isLoading && <ListSkeleton />}
                {!isLoading && count === 0 && <EmptyState />}
                {!isLoading && count > 0 && (
                    <>
                        <p className="pb-2 text-xs text-muted-foreground">
                            {t("features.explore.results", { count })}
                        </p>
                        <ul className="flex flex-col gap-1.5">
                            {establishments.map((establishment) => (
                                <li key={establishment.id}>
                                    <EstablishmentCard
                                        establishment={establishment}
                                        href={routes.HostDetail({
                                            id: establishment.id,
                                        })}
                                    />
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </div>
        </div>
    );
}

function ListSkeleton() {
    return (
        <div className="flex flex-col gap-1.5">
            {Array.from({ length: 3 }).map((_, index) => (
                <div key={index} className="overflow-hidden rounded-3xl bg-white">
                    <Skeleton className="h-64 w-full rounded-3xl" />
                    <div className="flex flex-col gap-3 px-3 py-4">
                        <Skeleton className="h-6 w-3/4" />
                        <Skeleton className="h-4 w-1/2" />
                        <div className="flex items-center justify-between">
                            <Skeleton className="h-4 w-24" />
                            <Skeleton className="h-6 w-16" />
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

function EmptyState() {
    const t = useTranslations();
    return (
        <div className="flex flex-col items-center justify-center gap-3 py-16 text-center">
            <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                <KCompass
                    size={32}
                    primary="text-muted-foreground"
                    secondary="text-muted-foreground"
                />
            </div>
            <h2 className="text-lg font-semibold">{t("features.explore.empty.title")}</h2>
            <p className="max-w-sm text-sm text-muted-foreground">
                {t("features.explore.empty.description")}
            </p>
        </div>
    );
}

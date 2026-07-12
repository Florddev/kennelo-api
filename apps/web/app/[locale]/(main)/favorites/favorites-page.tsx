"use client";

import Link from "next/link";
import { useTranslations } from "next-intl";
import { FolderFavouriteStar } from "@solar-icons/react";
import { Button } from "@workspace/ui/components/button";
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@workspace/ui/components/empty";
import { useAuth } from "@/features/auth";
import { useFavorites } from "@/features/activities/hooks/use-favorites";
import { ActivityCard, ActivityCardSkeleton } from "@/features/explore/components/activity-card";
import { useNavigation } from "@/hooks/use-navigation";
import PageLayout from "@/components/layouts/page-layout";

function FavoritesContent() {
    const t = useTranslations();
    const { routes } = useNavigation();
    const { favorites, isLoading, hasNextPage, fetchNextPage, isFetchingNextPage } = useFavorites();

    if (isLoading) {
        return (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {Array.from({ length: 6 }).map((_, i) => (
                    <ActivityCardSkeleton key={i} />
                ))}
            </div>
        );
    }

    if (favorites.length === 0) {
        return (
            <Empty className="border">
                <EmptyMedia variant="icon">
                    <FolderFavouriteStar />
                </EmptyMedia>
                <EmptyHeader>
                    <EmptyTitle>{t("features.favorites.empty.title")}</EmptyTitle>
                    <EmptyDescription>{t("features.favorites.empty.description")}</EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button className="mx-auto" asChild>
                        <Link href={routes.Explore()}>{t("features.favorites.empty.cta")}</Link>
                    </Button>
                </EmptyContent>
            </Empty>
        );
    }

    return (
        <div className="flex flex-col gap-6">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {favorites.map((activity) => (
                    <ActivityCard
                        key={activity.id}
                        activity={activity}
                        href={routes.HostDetail({ id: activity.id })}
                    />
                ))}
            </div>
            {hasNextPage && (
                <Button
                    variant="flat"
                    className="mx-auto"
                    onClick={() => fetchNextPage()}
                    disabled={isFetchingNextPage}
                >
                    {t("features.favorites.loadMore")}
                </Button>
            )}
        </div>
    );
}

export default function FavoritesPage() {
    const t = useTranslations();
    const { isAuthenticated } = useAuth();
    const { routes } = useNavigation();

    return (
        <div className="p-4 md:p-6">
            <PageLayout Icon={FolderFavouriteStar} title={t("features.favorites.title")}>
                {!isAuthenticated ? (
                    <div className="flex flex-col gap-4 py-1 text-sm">
                        <div className="flex flex-col gap-1">
                            <p className="text-lg text-primary font-semibold">
                                {t("features.favorites.please-login")}
                            </p>
                            <span className="text-muted-foreground">
                                {t("features.favorites.please-login-description")}
                            </span>
                        </div>
                        <Button variant="default" className="w-fit px-5" asChild>
                            <Link href={routes.Login()}>{t("common.actions.login")}</Link>
                        </Button>
                    </div>
                ) : (
                    <FavoritesContent />
                )}
            </PageLayout>
        </div>
    );
}

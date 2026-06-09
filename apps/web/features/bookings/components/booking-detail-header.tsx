"use client";

import { useTranslations } from "next-intl";
import { ArrowLeft } from "@solar-icons/react";

import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";

import { PhotosCarousel } from "@/components/media/photos-carousel";

export function BookingDetailHeader({
    imageUrls,
    activityName,
    onBack,
    isCapacitorApp,
}: {
    imageUrls: string[];
    activityName: string;
    onBack: () => void;
    isCapacitorApp: boolean;
}) {
    const t = useTranslations();

    return (
        <>
            <div
                className={cn(
                    "flex justify-between items-center p-2 px-4",
                    isCapacitorApp && "mt-[var(--mobile-top-margin)]",
                )}
            >
                <Button size="icon-sm" variant="flat" onClick={onBack}>
                    <ArrowLeft />
                </Button>
            </div>

            {imageUrls.length > 0 ? (
                <PhotosCarousel
                    images={imageUrls}
                    altPrefix={t("features.bookings.detail.photosOf", { name: activityName })}
                    className="rounded-lg mx-3 md:mx-0"
                />
            ) : (
                <div className="aspect-[4/3] bg-muted" />
            )}
        </>
    );
}

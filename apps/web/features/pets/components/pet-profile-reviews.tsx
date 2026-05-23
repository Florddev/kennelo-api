"use client";

import { Star } from "@solar-icons/react";
import type { PetModel } from "@workspace/modules/pets";
import { Spinner } from "@workspace/ui/components/spinner";
import { useTranslations } from "next-intl";
import { usePetReviews } from "../hooks/use-pet-reviews";
import { PetReviewCard } from "./pet-review-card";

export function PetProfileReviews({ pet }: { pet: PetModel }) {
    const t = useTranslations();
    const { reviews, isLoading } = usePetReviews(pet.id);

    if (isLoading) {
        return (
            <div className="flex justify-center py-10">
                <Spinner />
            </div>
        );
    }

    if (reviews.length === 0) {
        return (
            <div className="rounded-2xl border border-dashed p-10 flex flex-col items-center gap-3 text-center">
                <Star weight="BoldDuotone" className="size-10 text-muted-foreground/20" />
                <div className="space-y-1">
                    <p className="font-medium text-sm">{t("features.pets.profile.reviewsEmpty")}</p>
                    <p className="text-xs text-muted-foreground">
                        {t("features.pets.profile.reviewsEmptyDescription")}
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-4">
            {reviews.map((review) => (
                <PetReviewCard key={review.id} review={review} pet={pet} />
            ))}
        </div>
    );
}

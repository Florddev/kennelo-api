"use client";

import { Star } from "lucide-react";
import { useTranslations } from "next-intl";

export function PetProfileReviews() {
    const t = useTranslations();

    return (
        <div className="rounded-2xl border bg-muted/30 p-10 flex flex-col items-center gap-3 text-center">
            <Star className="size-10 text-muted-foreground/20" />
            <div className="space-y-1">
                <p className="font-medium text-sm">{t("features.pets.profile.reviewsEmpty")}</p>
                <p className="text-xs text-muted-foreground">
                    {t("features.pets.profile.reviewsEmptyDescription")}
                </p>
            </div>
        </div>
    );
}

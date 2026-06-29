import { useTranslations } from "next-intl";
import { MessageCircle, Star } from "lucide-react";
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@workspace/ui/components/empty";
import { Skeleton } from "@workspace/ui/components/skeleton";
import type { ReviewModel } from "@workspace/modules/reviews";

import { ReviewItem } from "./review-item";

type HostReviewsSectionProps = {
    reviews: ReviewModel[];
    averageRating: number | null;
    reviewCount: number;
    isLoading: boolean;
};

export function HostReviewsSection({
    reviews,
    averageRating,
    reviewCount,
    isLoading,
}: HostReviewsSectionProps) {
    const t = useTranslations();

    function renderBody() {
        if (isLoading) {
            return (
                <div className="flex flex-col gap-4">
                    {Array.from({ length: 2 }).map((_, index) => (
                        <div key={index} className="flex items-start gap-2 px-1 py-3">
                            <Skeleton className="size-9 rounded-full" />
                            <div className="flex flex-1 flex-col gap-2">
                                <Skeleton className="h-4 w-32" />
                                <Skeleton className="h-3 w-full" />
                                <Skeleton className="h-3 w-2/3" />
                            </div>
                        </div>
                    ))}
                </div>
            );
        }

        if (reviews.length === 0) {
            return (
                <Empty className="rounded-2xl border py-8">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <MessageCircle />
                        </EmptyMedia>
                        <EmptyTitle>{t("features.host.detail.reviewsEmpty")}</EmptyTitle>
                        <EmptyDescription>
                            {t("features.host.detail.reviewsEmptyDescription")}
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            );
        }

        return (
            <div className="flex flex-col divide-y divide-zinc-100">
                {reviews.map((review) => (
                    <ReviewItem key={review.id} review={review} />
                ))}
            </div>
        );
    }

    return (
        <section data-slot="host-reviews-section" className="flex flex-col gap-3">
            <div className="flex items-center justify-between gap-2">
                <h2 className="text-lg font-semibold text-slate-900">
                    {t("features.host.detail.reviewsTitle")}
                </h2>
                {averageRating !== null && (
                    <span className="flex items-center gap-1 text-sm font-medium text-slate-900">
                        <Star className="size-4 fill-amber-400 stroke-amber-400" />
                        {averageRating.toFixed(1)}
                        <span className="text-zinc-400">
                            {t("features.host.detail.reviewsCount", { count: reviewCount })}
                        </span>
                    </span>
                )}
            </div>

            {renderBody()}
        </section>
    );
}

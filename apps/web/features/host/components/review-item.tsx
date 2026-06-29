"use client";

import { useLocale, useTranslations } from "next-intl";
import { Star } from "lucide-react";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import { formatDateAdaptive } from "@workspace/common";
import type { ReviewModel } from "@workspace/modules/reviews";
import { cn } from "@workspace/ui/lib/utils";

type ReviewItemProps = {
    review: ReviewModel;
};

function RatingStars({ rating }: { rating: number }) {
    return (
        <div data-slot="review-rating-stars" className="flex items-center gap-px">
            {Array.from({ length: 5 }).map((_, index) => (
                <Star
                    key={index}
                    className={cn(
                        "size-3",
                        index < rating
                            ? "fill-amber-400 stroke-amber-400"
                            : "fill-zinc-200 stroke-zinc-200",
                    )}
                />
            ))}
        </div>
    );
}

export function ReviewItem({ review }: ReviewItemProps) {
    const t = useTranslations();
    const locale = useLocale();
    const reviewer = review.reviewer;
    const reviewDate = formatDateAdaptive(review.publishedAt ?? review.createdAt, locale);

    return (
        <div data-slot="review-item" className="flex items-start gap-2 px-1 py-3">
            <Avatar className="size-9">
                {reviewer?.avatarUrl && (
                    <AvatarImage src={reviewer.avatarUrl} alt={reviewer.getFullName()} />
                )}
                <AvatarFallback>{reviewer?.getInitials() ?? "?"}</AvatarFallback>
            </Avatar>
            <div className="flex flex-1 flex-col gap-2 py-0.5">
                <div className="flex flex-col">
                    <div className="flex items-center justify-between gap-2">
                        <p className="text-sm font-medium text-black">
                            {reviewer?.getFullName() ?? t("features.host.detail.reviewAnonymous")}
                        </p>
                        <RatingStars rating={review.overallRating} />
                    </div>
                    <span className="text-xs text-zinc-400">{reviewDate}</span>
                </div>
                {review.comment && <p className="text-xs text-foreground">{review.comment}</p>}
                {review.response && (
                    <div className="mt-1 flex flex-col gap-1 rounded-2xl bg-zinc-50 p-3">
                        <p className="text-xs font-medium text-slate-700">
                            {t("features.host.detail.hostResponse")}
                        </p>
                        <p className="text-xs text-foreground">{review.response.response}</p>
                    </div>
                )}
            </div>
        </div>
    );
}

import { useTranslations } from "next-intl";
import { MessageCircle } from "lucide-react";
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@workspace/ui/components/empty";

import { ReviewItem, type ReviewItemData } from "./review-item";

type HostReviewsSectionProps = {
    reviews: ReviewItemData[];
};

export function HostReviewsSection({ reviews }: HostReviewsSectionProps) {
    const t = useTranslations();

    if (reviews.length === 0) {
        return (
            <section className="flex flex-col gap-3">
                <h2 className="text-lg font-semibold text-slate-900">
                    {t("features.host.detail.reviewsTitle")}
                </h2>
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
            </section>
        );
    }

    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-lg font-semibold text-slate-900">
                {t("features.host.detail.reviewsTitle")}
            </h2>
            <div className="flex flex-col">
                {reviews.map((review) => (
                    <ReviewItem key={review.id} review={review} />
                ))}
            </div>
        </section>
    );
}

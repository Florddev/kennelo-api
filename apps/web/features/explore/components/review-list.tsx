import { useTranslations } from "next-intl";
import { Star } from "lucide-react";
import { Avatar, AvatarFallback } from "@workspace/ui/components/avatar";
import { cn } from "@workspace/ui/lib/utils";

type Review = {
    id: string;
    author: string;
    initials: string;
    rating: number;
    yearsOnKennelo: number;
    monthLabel: string;
    body: string;
};

const DEMO_REVIEWS: Review[] = [
    {
        id: "alex",
        author: "Alex",
        initials: "A",
        rating: 4,
        yearsOnKennelo: 4,
        monthLabel: "juillet 2025",
        body: "La maison est chaleureuse et authentique. Le jardin est absolument splendide. Les chambres sont très spacieuses, ...",
    },
    {
        id: "nicola",
        author: "Nicola",
        initials: "N",
        rating: 5,
        yearsOnKennelo: 0.25,
        monthLabel: "janvier 2026",
        body: "Glorieux ! Il est très facile de comprendre pourquoi il est super difficile de trouver des dates où le logement n'est pas déjà réservé...",
    },
];

function RatingStars({ rating }: { rating: number }) {
    return (
        <div className="flex items-center gap-px">
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

export function ReviewList() {
    const t = useTranslations();

    return (
        <div className="flex flex-col">
            {DEMO_REVIEWS.map((review) => {
                const timeLabel =
                    review.yearsOnKennelo >= 1
                        ? t("features.explore.detail.yearsOnKennelo", {
                              count: Math.floor(review.yearsOnKennelo),
                          })
                        : t("features.explore.detail.monthsOnKennelo", {
                              count: Math.round(review.yearsOnKennelo * 12),
                          });

                return (
                    <div key={review.id} className="flex items-start gap-2 px-1 py-3">
                        <Avatar className="size-9">
                            <AvatarFallback>{review.initials}</AvatarFallback>
                        </Avatar>
                        <div className="flex flex-1 flex-col gap-2 py-0.5">
                            <div className="flex flex-col">
                                <div className="flex items-center justify-between">
                                    <p className="text-sm font-medium text-black">
                                        {review.author}
                                    </p>
                                    <RatingStars rating={review.rating} />
                                </div>
                                <div className="flex items-center justify-between text-xs text-zinc-400">
                                    <span>{timeLabel}</span>
                                    <span>{review.monthLabel}</span>
                                </div>
                            </div>
                            <p className="text-xs text-foreground">{review.body}</p>
                            <button
                                type="button"
                                className="self-start text-xs font-medium underline"
                            >
                                {t("features.explore.detail.readMore")}
                            </button>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

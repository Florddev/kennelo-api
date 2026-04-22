import { Star } from "lucide-react";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import { cn } from "@workspace/ui/lib/utils";

export type ReviewItemData = {
    id: string;
    authorName: string;
    authorInitials: string;
    authorAvatarUrl?: string | null;
    rating: number;
    createdAt: string;
    body: string;
    timeOnPlatformLabel?: string;
};

type ReviewItemProps = {
    review: ReviewItemData;
};

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

export function ReviewItem({ review }: ReviewItemProps) {
    return (
        <div className="flex items-start gap-2 px-1 py-3">
            <Avatar className="size-9">
                {review.authorAvatarUrl && (
                    <AvatarImage src={review.authorAvatarUrl} alt={review.authorName} />
                )}
                <AvatarFallback>{review.authorInitials}</AvatarFallback>
            </Avatar>
            <div className="flex flex-1 flex-col gap-2 py-0.5">
                <div className="flex flex-col">
                    <div className="flex items-center justify-between">
                        <p className="text-sm font-medium text-black">{review.authorName}</p>
                        <RatingStars rating={review.rating} />
                    </div>
                    <div className="flex items-center justify-between text-xs text-zinc-400">
                        {review.timeOnPlatformLabel && <span>{review.timeOnPlatformLabel}</span>}
                        <span>{review.createdAt}</span>
                    </div>
                </div>
                <p className="text-xs text-foreground">{review.body}</p>
            </div>
        </div>
    );
}

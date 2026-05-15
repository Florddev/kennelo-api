"use client";

import { UserAvatar } from "@/features/auth";
import { Star } from "@solar-icons/react";
import { formatDateAdaptive } from "@workspace/common";
import type { PetReviewModel } from "@workspace/modules/pets";

export function PetReviewCard({ review }: { review: PetReviewModel }) {
    const filledStars = 3;

    return (
        <div className="flex flex-col gap-3">
            <div className="flex items-center justify-between gap-3">
                <div className="flex gap-3 w-full justify-between">
                    {review.reviewer && (
                        <UserAvatar
                            user={{
                                avatarUrl: review.reviewer.avatarUrl,
                                getFullName: review.getReviewerName,
                            }}
                            className="size-8"
                        />
                    )}
                    <div className="flex flex-col gap-1 w-full">
                        <div className="flex flex-col w-full">
                            <div className="flex justify-between items-center">
                                <span className="text-sm font-semibold">
                                    {review.getReviewerName()}
                                </span>
                                <div className="flex gap-0.5">
                                    {Array.from({ length: 5 }).map((_, i) =>
                                        i < filledStars ? (
                                            <Star
                                                weight="Bold"
                                                key={i}
                                                className="size-3.5 text-amber-400"
                                            />
                                        ) : (
                                            <Star
                                                key={i}
                                                weight="Bold"
                                                className="size-3.5 text-muted-foreground/20"
                                            />
                                        ),
                                    )}
                                </div>
                            </div>
                            <div className="flex justify-between items-start text-xs text-muted-foreground">
                                {formatDateAdaptive(review.createdAt)}
                                <span className="px-0.5 font-semibold">{review.rating}</span>
                            </div>
                        </div>
                        {review.comment && (
                            <p className="text-sm text-primary leading-relaxed">{review.comment}</p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

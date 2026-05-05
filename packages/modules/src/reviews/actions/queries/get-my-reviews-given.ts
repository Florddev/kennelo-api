import { api } from "@workspace/common";
import type { ReviewerType } from "../../types/reviewer-type.type";
import type { ReviewDto } from "../../models/dtos/review.dto";
import { ReviewModel } from "../../models/review.model";

export async function getMyReviewsGiven(input?: {
    perPage?: number;
    reviewerType?: ReviewerType;
    minRating?: number;
}): Promise<ReviewModel[]> {
    const params: Record<string, string | number | boolean> = {};

    if (input?.perPage != null) params.per_page = input.perPage;
    if (input?.reviewerType) params.reviewer_type = input.reviewerType;
    if (input?.minRating != null) params.min_rating = input.minRating;

    const response = await api.get<ReviewDto[]>(
        "/user/reviews/given",
        Object.keys(params).length > 0 ? params : undefined,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(ReviewModel.from);
}

import { api } from "@workspace/common";
import type { ReviewDto } from "../../models/dtos/review.dto";
import { ReviewModel } from "../../models/review.model";
import type { CreateReviewInput } from "../../validators/create-review.schema";

export async function createReview(
    bookingId: string,
    input: CreateReviewInput,
): Promise<ReviewModel> {
    const response = await api.post<ReviewDto>(`/bookings/${bookingId}/reviews`, {
        overall_rating: input.overallRating,
        comment: input.comment,
        private_feedback: input.privateFeedback,
        would_recommend: input.wouldRecommend,
        criteria_scores: input.criteriaScores?.map((s) => ({
            code: s.code,
            score: s.score,
        })),
    });

    if (!response.data) {
        throw new Error("Failed to create review");
    }

    return ReviewModel.from(response.data);
}

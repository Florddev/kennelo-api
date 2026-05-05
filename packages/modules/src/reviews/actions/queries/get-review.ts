import { api } from "@workspace/common";
import type { ReviewDto } from "../../models/dtos/review.dto";
import { ReviewModel } from "../../models/review.model";

export async function getReview(reviewId: string): Promise<ReviewModel> {
    const response = await api.get<ReviewDto>(`/reviews/${reviewId}`);

    if (!response.data) {
        throw new Error("Review not found");
    }

    return ReviewModel.from(response.data);
}

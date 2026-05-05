import { api } from "@workspace/common";
import type { ReviewResponseDto } from "../../models/dtos/review-response.dto";
import { ReviewResponseModel } from "../../models/review-response.model";
import type { CreateReviewResponseInput } from "../../validators/create-review-response.schema";

export async function createReviewResponse(
    reviewId: string,
    input: CreateReviewResponseInput,
): Promise<ReviewResponseModel> {
    const response = await api.post<ReviewResponseDto>(`/reviews/${reviewId}/response`, {
        response: input.response,
    });

    if (!response.data) {
        throw new Error("Failed to create review response");
    }

    return ReviewResponseModel.from(response.data);
}

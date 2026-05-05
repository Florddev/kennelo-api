import { UserModel } from "../../users/models/user.model";
import type { ReviewResponseDto } from "./dtos/review-response.dto";

export class ReviewResponseModel {
    private constructor(
        public readonly id: string,
        public readonly reviewId: string,
        public readonly responderId: string,
        public readonly response: string,
        public readonly responder: UserModel | null,
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: ReviewResponseDto): ReviewResponseModel {
        return new ReviewResponseModel(
            dto.id,
            dto.review_id,
            dto.responder_id,
            dto.response,
            dto.responder ? UserModel.from(dto.responder) : null,
            dto.created_at,
            dto.updated_at,
        );
    }
}

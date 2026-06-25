import { UserModel } from "../../users/models/user.model";
import type { ReviewerType } from "../types/reviewer-type.type";
import type { ReviewDto } from "./dtos/review.dto";
import { ReviewCriteriaScoreModel } from "./review-criteria-score.model";
import { ReviewResponseModel } from "./review-response.model";

export class ReviewModel {
    private constructor(
        public readonly id: string,
        public readonly bookingId: string,
        public readonly reviewerId: string,
        public readonly reviewerType: ReviewerType,
        public readonly overallRating: number,
        public readonly comment: string | null,
        public readonly privateFeedback: string | null | undefined,
        public readonly wouldRecommend: boolean,
        public readonly isPublished: boolean,
        public readonly publishedAt: string | null,
        public readonly reviewer: UserModel | null,
        public readonly criteriaScores: ReviewCriteriaScoreModel[],
        public readonly response: ReviewResponseModel | null,
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: ReviewDto): ReviewModel {
        return new ReviewModel(
            dto.id,
            dto.booking_id,
            dto.reviewer_id,
            dto.reviewer_type,
            Number(dto.overall_rating),
            dto.comment,
            dto.private_feedback,
            dto.would_recommend,
            dto.is_published,
            dto.published_at,
            dto.reviewer ? UserModel.from(dto.reviewer) : null,
            dto.criteria_scores ? dto.criteria_scores.map(ReviewCriteriaScoreModel.from) : [],
            dto.response ? ReviewResponseModel.from(dto.response) : null,
            dto.created_at,
            dto.updated_at,
        );
    }

    isUserReview(): boolean {
        return this.reviewerType === "user";
    }

    isActivityReview(): boolean {
        return this.reviewerType === "activity";
    }

    hasResponse(): boolean {
        return this.response !== null;
    }
}

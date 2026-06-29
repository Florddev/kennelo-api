import type { UserDto } from "../../../users/models/dtos/user.dto";
import type { ReviewerType } from "../../types/reviewer-type.type";
import type { ReviewCriteriaScoreDto } from "./review-criteria-score.dto";
import type { ReviewResponseDto } from "./review-response.dto";

export type ReviewDto = {
    id: string;
    booking_id: string;
    reviewer_id: string;
    reviewer_type: ReviewerType;
    overall_rating: string | number;
    comment: string | null;
    private_feedback?: string | null;
    would_recommend: boolean;
    is_published: boolean;
    published_at: string | null;
    reviewer?: UserDto | null;
    criteria_scores?: ReviewCriteriaScoreDto[];
    response?: ReviewResponseDto | null;
    created_at: string;
    updated_at: string;
};

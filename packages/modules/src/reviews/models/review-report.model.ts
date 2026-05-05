import { UserModel } from "../../users/models/user.model";
import type { ReviewReportReason } from "../types/review-report-reason.type";
import type { ReviewReportStatus } from "../types/review-report-status.type";
import type { ReviewReportDto } from "./dtos/review-report.dto";
import { ReviewModel } from "./review.model";

export class ReviewReportModel {
    private constructor(
        public readonly id: string,
        public readonly reviewId: string,
        public readonly reporterId: string,
        public readonly reason: ReviewReportReason,
        public readonly description: string | null,
        public readonly status: ReviewReportStatus,
        public readonly reviewedAt: string | null,
        public readonly reporter: UserModel | null,
        public readonly review: ReviewModel | null,
        public readonly createdAt: string,
        public readonly updatedAt: string,
    ) {}

    static from(dto: ReviewReportDto): ReviewReportModel {
        return new ReviewReportModel(
            dto.id,
            dto.review_id,
            dto.reporter_id,
            dto.reason,
            dto.description,
            dto.status,
            dto.reviewed_at,
            dto.reporter ? UserModel.from(dto.reporter) : null,
            dto.review ? ReviewModel.from(dto.review) : null,
            dto.created_at,
            dto.updated_at,
        );
    }

    isPending(): boolean {
        return this.status === "pending";
    }

    isReviewed(): boolean {
        return this.status === "reviewed";
    }

    isRejected(): boolean {
        return this.status === "rejected";
    }

    isRemoved(): boolean {
        return this.status === "removed";
    }
}

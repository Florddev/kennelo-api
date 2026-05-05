import type { UserDto } from "../../../users/models/dtos/user.dto";
import type { ReviewReportReason } from "../../types/review-report-reason.type";
import type { ReviewReportStatus } from "../../types/review-report-status.type";
import type { ReviewDto } from "./review.dto";

export type ReviewReportDto = {
    id: string;
    review_id: string;
    reporter_id: string;
    reason: ReviewReportReason;
    description: string | null;
    status: ReviewReportStatus;
    reviewed_at: string | null;
    reporter?: UserDto | null;
    review?: ReviewDto | null;
    created_at: string;
    updated_at: string;
};

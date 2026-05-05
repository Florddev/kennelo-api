import { api } from "@workspace/common";
import type { ReviewReportDto } from "../../models/dtos/review-report.dto";
import { ReviewReportModel } from "../../models/review-report.model";
import type { CreateReviewReportInput } from "../../validators/create-review-report.schema";

export async function createReviewReport(
    reviewId: string,
    input: CreateReviewReportInput,
): Promise<ReviewReportModel> {
    const response = await api.post<ReviewReportDto>(`/reviews/${reviewId}/reports`, {
        reason: input.reason,
        description: input.description,
    });

    if (!response.data) {
        throw new Error("Failed to create review report");
    }

    return ReviewReportModel.from(response.data);
}

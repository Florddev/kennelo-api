import { api } from "@workspace/common";
import type { ReviewReportDto } from "../../models/dtos/review-report.dto";
import { ReviewReportModel } from "../../models/review-report.model";
import type { UpdateReviewReportInput } from "../../validators/update-review-report.schema";

export async function updateReviewReport(
    reportId: string,
    input: UpdateReviewReportInput,
): Promise<ReviewReportModel> {
    const response = await api.put<ReviewReportDto>(`/admin/review-reports/${reportId}`, {
        status: input.status,
    });

    if (!response.data) {
        throw new Error("Failed to update review report");
    }

    return ReviewReportModel.from(response.data);
}

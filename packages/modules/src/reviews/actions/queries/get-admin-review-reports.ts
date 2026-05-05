import { api } from "@workspace/common";
import type { ReviewReportStatus } from "../../types/review-report-status.type";
import type { ReviewReportDto } from "../../models/dtos/review-report.dto";
import { ReviewReportModel } from "../../models/review-report.model";

export async function getAdminReviewReports(input?: {
    perPage?: number;
    status?: ReviewReportStatus;
}): Promise<ReviewReportModel[]> {
    const params: Record<string, string | number | boolean> = {};

    if (input?.perPage != null) params.per_page = input.perPage;
    if (input?.status) params.status = input.status;

    const response = await api.get<ReviewReportDto[]>(
        "/admin/review-reports",
        Object.keys(params).length > 0 ? params : undefined,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(ReviewReportModel.from);
}

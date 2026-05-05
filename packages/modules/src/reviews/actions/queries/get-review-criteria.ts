import { api } from "@workspace/common";
import type { CriteriaApplicableTo } from "../../types/criteria-applicable-to.type";
import type { ReviewCriteriaDefinitionDto } from "../../models/dtos/review-criteria-definition.dto";
import { ReviewCriteriaDefinitionModel } from "../../models/review-criteria-definition.model";

export async function getReviewCriteria(input?: {
    applicableTo?: CriteriaApplicableTo;
}): Promise<ReviewCriteriaDefinitionModel[]> {
    const params: Record<string, string | number | boolean> = {};

    if (input?.applicableTo) params.applicable_to = input.applicableTo;

    const response = await api.get<ReviewCriteriaDefinitionDto[]>(
        "/review-criteria",
        Object.keys(params).length > 0 ? params : undefined,
    );

    if (!response.data) {
        return [];
    }

    return response.data.map(ReviewCriteriaDefinitionModel.from);
}

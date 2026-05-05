import type { ReviewCriteriaDefinitionDto } from "./review-criteria-definition.dto";

export type ReviewCriteriaScoreDto = {
    id: string;
    criteria_code: string;
    score: number;
    definition?: ReviewCriteriaDefinitionDto | null;
};

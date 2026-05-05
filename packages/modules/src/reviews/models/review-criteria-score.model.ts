import type { ReviewCriteriaScoreDto } from "./dtos/review-criteria-score.dto";
import { ReviewCriteriaDefinitionModel } from "./review-criteria-definition.model";

export class ReviewCriteriaScoreModel {
    private constructor(
        public readonly id: string,
        public readonly criteriaCode: string,
        public readonly score: number,
        public readonly definition: ReviewCriteriaDefinitionModel | null,
    ) {}

    static from(dto: ReviewCriteriaScoreDto): ReviewCriteriaScoreModel {
        return new ReviewCriteriaScoreModel(
            dto.id,
            dto.criteria_code,
            dto.score,
            dto.definition ? ReviewCriteriaDefinitionModel.from(dto.definition) : null,
        );
    }
}

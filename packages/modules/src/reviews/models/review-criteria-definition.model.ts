import type { CriteriaApplicableTo } from "../types/criteria-applicable-to.type";
import type { ReviewCriteriaDefinitionDto } from "./dtos/review-criteria-definition.dto";

export class ReviewCriteriaDefinitionModel {
    private constructor(
        public readonly id: string,
        public readonly code: string,
        public readonly label: string,
        public readonly applicableTo: CriteriaApplicableTo,
        public readonly sortOrder: number,
    ) {}

    static from(dto: ReviewCriteriaDefinitionDto): ReviewCriteriaDefinitionModel {
        return new ReviewCriteriaDefinitionModel(
            dto.id,
            dto.code,
            dto.label,
            dto.applicable_to,
            dto.sort_order,
        );
    }

    appliesToUser(): boolean {
        return this.applicableTo === "user" || this.applicableTo === "both";
    }

    appliesToActivity(): boolean {
        return this.applicableTo === "activity" || this.applicableTo === "both";
    }
}

import type { CriteriaApplicableTo } from "../../types/criteria-applicable-to.type";

export type ReviewCriteriaDefinitionDto = {
    id: string;
    code: string;
    label: string;
    applicable_to: CriteriaApplicableTo;
    sort_order: number;
};

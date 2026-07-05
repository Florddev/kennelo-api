export const PROSPECT_STATUS = {
    NON_CONTACTE: "non_contacte",
    CONTACTE: "contacte",
    RELANCE: "relance",
    INSCRIT: "inscrit",
    REFUSE: "refuse",
} as const;

export type ProspectStatusValue = (typeof PROSPECT_STATUS)[keyof typeof PROSPECT_STATUS];

export const PROSPECT_STATUS_VALUES: ProspectStatusValue[] = Object.values(PROSPECT_STATUS);

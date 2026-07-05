export const PROSPECT_CONTACT_TYPE = {
    CALL: "call",
    EMAIL: "email",
    SMS: "sms",
    MEETING: "meeting",
    OTHER: "other",
} as const;

export type ProspectContactTypeValue =
    (typeof PROSPECT_CONTACT_TYPE)[keyof typeof PROSPECT_CONTACT_TYPE];

export const PROSPECT_CONTACT_TYPE_VALUES: ProspectContactTypeValue[] =
    Object.values(PROSPECT_CONTACT_TYPE);

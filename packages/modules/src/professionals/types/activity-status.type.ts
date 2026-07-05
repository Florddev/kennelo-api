export const ACTIVITY_STATUS = {
    PENDING: "pending",
    APPROVED: "approved",
    REJECTED: "rejected",
} as const;

export type ActivityStatusValue = (typeof ACTIVITY_STATUS)[keyof typeof ACTIVITY_STATUS];

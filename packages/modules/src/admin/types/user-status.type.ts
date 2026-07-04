export const USER_STATUS = {
    INACTIVE: 0,
    ACTIVE: 1,
    BANNED: 2,
} as const;

export type UserStatusValue = (typeof USER_STATUS)[keyof typeof USER_STATUS];

export type UserStatusLabel = "active" | "inactive" | "banned";

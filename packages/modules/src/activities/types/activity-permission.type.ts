export type ActivityPermission =
    | "update_activity"
    | "manage_cycles"
    | "manage_availabilities"
    | "manage_bookings"
    | "manage_messages";

export const ACTIVITY_PERMISSIONS: ActivityPermission[] = [
    "update_activity",
    "manage_cycles",
    "manage_availabilities",
    "manage_bookings",
    "manage_messages",
];

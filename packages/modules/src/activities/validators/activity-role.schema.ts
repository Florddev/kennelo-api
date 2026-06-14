import { z } from "zod";

export const activityRoleSchema = z.object({
    name: z.string().min(1, "Name is required").max(100),
    permissions: z.array(
        z.enum([
            "update_activity",
            "manage_cycles",
            "manage_availabilities",
            "manage_bookings",
            "manage_messages",
        ]),
    ),
});

export type ActivityRoleInput = z.infer<typeof activityRoleSchema>;

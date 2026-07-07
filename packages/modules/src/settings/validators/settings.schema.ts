import { z } from "zod";

export const updateSettingsSchema = z.object({
    userServiceFeeRate: z.coerce.number().min(0).max(1),
    hostCommissionRate: z.coerce.number().min(0).max(1),
    acceptanceWindowHours: z.coerce.number().int().min(0),
    payoutDelayHours: z.coerce.number().int().min(0),
    reminderAfterHours: z.coerce.number().int().min(0),
    currency: z.string().length(3),
    tier3Enabled: z.boolean(),
    softDisableActivities: z.boolean(),
    softDisableCycles: z.boolean(),
    softDisablePhotos: z.boolean(),
});

export type UpdateSettingsInput = z.infer<typeof updateSettingsSchema>;

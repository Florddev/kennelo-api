import { z } from "zod";

export const updateSubscriptionPlanSchema = z.object({
    name: z.string().min(1).max(100),
    description: z.string().max(1000).nullable().optional(),
    priceMonthly: z.coerce.number().min(0),
    commissionRate: z.coerce.number().min(0).max(1),
    maxActivities: z.coerce.number().int().min(-1),
    maxCyclesPerActivity: z.coerce.number().int().min(-1),
    maxPhotos: z.coerce.number().int().min(-1),
    isActive: z.boolean(),
});

export type UpdateSubscriptionPlanInput = z.infer<typeof updateSubscriptionPlanSchema>;

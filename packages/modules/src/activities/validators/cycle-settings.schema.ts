import { z } from "zod";

export const cycleSettingSchema = z.object({
    animalTypeId: z.string().uuid(),
    maxCapacity: z.coerce.number().int().min(1),
    price: z.coerce.number().min(0),
    sumWeekdays: z.coerce.number().int().min(0).max(127).optional(),
});

export type CycleSettingInput = z.infer<typeof cycleSettingSchema>;

export const upsertCycleSettingsSchema = z.object({
    settings: z.array(cycleSettingSchema),
});

export type UpsertCycleSettingsInput = z.infer<typeof upsertCycleSettingsSchema>;

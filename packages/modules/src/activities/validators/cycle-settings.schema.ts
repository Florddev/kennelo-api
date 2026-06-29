import { z } from "zod";

export const cycleSettingPriceSchema = z.object({
    weekday: z.coerce.number().int(),
    price: z.coerce.number().min(0),
});

export type CycleSettingPriceInput = z.infer<typeof cycleSettingPriceSchema>;

export const cycleSettingSchema = z.object({
    animalTypeId: z.string().uuid(),
    maxCapacity: z.coerce.number().int().min(1),
    prices: z.array(cycleSettingPriceSchema),
});

export type CycleSettingInput = z.infer<typeof cycleSettingSchema>;

export const upsertCycleSettingsSchema = z.object({
    settings: z.array(cycleSettingSchema),
});

export type UpsertCycleSettingsInput = z.infer<typeof upsertCycleSettingsSchema>;

import { z } from "zod";

export const upsertClosedWeekDaysSchema = z.object({
    sumWeekdays: z.coerce.number().int().min(0).max(127),
});

export type UpsertClosedWeekDaysInput = z.infer<typeof upsertClosedWeekDaysSchema>;

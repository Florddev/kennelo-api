import { z } from "zod";

export const createCycleSchema = z.object({
    startDate: z.union([z.string().regex(/^\d{4}-\d{2}-\d{2}$/), z.literal("")]).optional(),
    endDate: z.union([z.string().regex(/^\d{4}-\d{2}-\d{2}$/), z.literal("")]).optional(),
    priority: z.coerce.number().int().min(0).optional(),
    isActive: z.boolean().optional(),
});

export type CreateCycleInput = z.infer<typeof createCycleSchema>;

export const updateCycleSchema = createCycleSchema;

export type UpdateCycleInput = z.infer<typeof updateCycleSchema>;

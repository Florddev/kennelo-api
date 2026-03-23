import { z } from "zod";

export const createCapacitySchema = z.object({
    animalTypeId: z.coerce.number().int().positive(),
    maxCapacity: z.coerce.number().int().min(1),
    pricePerNight: z.coerce.number().min(0),
});

export type CreateCapacityInput = z.infer<typeof createCapacitySchema>;

export const updateCapacitySchema = z.object({
    maxCapacity: z.coerce.number().int().min(1).optional(),
    pricePerNight: z.coerce.number().min(0).optional(),
});

export type UpdateCapacityInput = z.infer<typeof updateCapacitySchema>;

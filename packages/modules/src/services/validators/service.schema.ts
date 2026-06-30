import { z } from "zod";

export const createServiceSchema = z.object({
    name: z.string().min(1),
    animalTypeId: z.string().min(1),
    price: z.coerce.number().min(0),
    description: z.string().nullable().optional(),
    isIncluded: z.boolean().optional(),
});

export type CreateServiceInput = z.infer<typeof createServiceSchema>;

export const updateServiceSchema = createServiceSchema.partial();

export type UpdateServiceInput = z.infer<typeof updateServiceSchema>;

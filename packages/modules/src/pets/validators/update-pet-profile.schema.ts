import { z } from "zod";

export const updatePetProfileSchema = z.object({
    birthDate: z.string().nullable().optional(),
    weight: z.number().min(0).nullable().optional(),
    adoptionDate: z.string().nullable().optional(),
    about: z.string().nullable().optional(),
});

export type UpdatePetProfileInput = z.infer<typeof updatePetProfileSchema>;

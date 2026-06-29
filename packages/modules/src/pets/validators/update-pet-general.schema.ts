import { z } from "zod";

export const updatePetGeneralSchema = z.object({
    name: z.string().min(1).max(255),
    animalTypeId: z.string(),
    animalBreedId: z.string().nullable().optional(),
    breed: z.string().nullable().optional(),
    sex: z.string().optional(),
    birthDate: z.string().nullable().optional(),
    weight: z.number().min(0).nullable().optional(),
    adoptionDate: z.string().nullable().optional(),
    about: z.string().nullable().optional(),
});

export type UpdatePetGeneralInput = z.infer<typeof updatePetGeneralSchema>;

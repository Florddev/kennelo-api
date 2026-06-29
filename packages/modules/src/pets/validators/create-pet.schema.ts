import { z } from "zod";

export const createPetSchema = z.object({
    animalTypeId: z.string().min(1),
    animalBreedId: z.string().nullable().optional(),
    name: z.string().min(1).max(255),
    breed: z.string().max(255).nullable().optional(),
    birthDate: z.string().nullable().optional(),
    sex: z.enum(["male", "female", "unknown"]).nullable().optional(),
    weight: z.number().min(0).nullable().optional(),
    isSterilized: z.boolean().nullable().optional(),
    hasMicrochip: z.boolean().optional(),
    microchipNumber: z.string().max(50).nullable().optional(),
    adoptionDate: z.string().nullable().optional(),
    about: z.string().nullable().optional(),
    healthNotes: z.string().nullable().optional(),
});

export type CreatePetInput = z.infer<typeof createPetSchema>;

import { z } from "zod";

export const updatePetHealthSchema = z.object({
    isSterilized: z.boolean().nullable().optional(),
    hasMicrochip: z.boolean().optional(),
    microchipNumber: z.string().max(50).nullable().optional(),
    healthNotes: z.string().nullable().optional(),
});

export type UpdatePetHealthInput = z.infer<typeof updatePetHealthSchema>;

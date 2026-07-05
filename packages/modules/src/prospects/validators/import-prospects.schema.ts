import { z } from "zod";

export const importProspectsSchema = z.object({
    location: z.string().min(1, "La localisation est requise").max(255),
    searchTerms: z.array(z.string().min(1).max(255)).min(1).optional(),
    maxResults: z.number().int().min(1).max(500).optional(),
});

export type ImportProspectsInput = z.infer<typeof importProspectsSchema>;

import { z } from "zod";

const petAttributeItemSchema = z.object({
    attributeDefinitionId: z.number().int().positive(),
    attributeOptionId: z.string().nullable().optional(),
    valueText: z.string().nullable().optional(),
    valueInteger: z.number().int().nullable().optional(),
    valueDecimal: z.number().nullable().optional(),
    valueBoolean: z.boolean().nullable().optional(),
    valueDate: z.string().nullable().optional(),
});

export const upsertPetAttributesSchema = z.object({
    attributes: z.array(petAttributeItemSchema).min(1),
});

export type UpsertPetAttributesInput = z.infer<typeof upsertPetAttributesSchema>;

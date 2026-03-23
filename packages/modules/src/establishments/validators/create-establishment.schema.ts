import { z } from "zod";

export const addressSchema = z.object({
    line1: z.string().max(255).optional().or(z.literal("")),
    line2: z.union([z.string().max(255), z.literal("")]).optional(),
    city: z.string().max(100).optional().or(z.literal("")),
    postalCode: z.string().max(20).optional().or(z.literal("")),
    region: z.union([z.string().max(100), z.literal("")]).optional(),
    country: z.string().max(100).optional().or(z.literal("")),
});

export const createEstablishmentSchema = z.object({
    name: z.string().min(1).max(255),
    description: z.union([z.string().max(2000), z.literal("")]).optional(),
    phone: z.union([z.string().max(20), z.literal("")]).optional(),
    email: z.union([z.string().email().max(255), z.literal("")]).optional(),
    website: z.union([z.string().url().max(255), z.literal("")]).optional(),
    siret: z.union([z.string().max(14), z.literal("")]).optional(),
    address: addressSchema.optional(),
});

export type CreateEstablishmentInput = z.infer<typeof createEstablishmentSchema>;

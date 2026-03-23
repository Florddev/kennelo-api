import { z } from "zod";

import { addressSchema } from "./create-establishment.schema";

export const updateEstablishmentSchema = z.object({
    name: z.string().min(1).max(255),
    description: z.union([z.string().max(2000), z.literal("")]).optional(),
    phone: z.union([z.string().max(20), z.literal("")]).optional(),
    email: z.union([z.string().email().max(255), z.literal("")]).optional(),
    website: z.union([z.string().url().max(255), z.literal("")]).optional(),
    siret: z.union([z.string().max(14), z.literal("")]).optional(),
    address: addressSchema.optional(),
});

export type UpdateEstablishmentInput = z.infer<typeof updateEstablishmentSchema>;

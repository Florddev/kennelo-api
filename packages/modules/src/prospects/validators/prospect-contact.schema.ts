import { z } from "zod";
import { PROSPECT_CONTACT_TYPE_VALUES } from "../types/prospect-contact-type.type";

export const prospectContactSchema = z.object({
    type: z.enum(PROSPECT_CONTACT_TYPE_VALUES as [string, ...string[]]),
    contactedAt: z.string().optional(),
    outcome: z.string().max(255).nullable().optional(),
    notes: z.string().max(2000).nullable().optional(),
});

export type ProspectContactInput = z.infer<typeof prospectContactSchema>;

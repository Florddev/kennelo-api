import { z } from "zod";

export const prospectNoteSchema = z.object({
    body: z.string().min(1, "La note ne peut pas être vide").max(2000),
});

export type ProspectNoteInput = z.infer<typeof prospectNoteSchema>;

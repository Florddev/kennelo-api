import { z } from "zod";

export const banUserSchema = z.object({
    reason: z.string().min(1, "A reason is required").max(500),
    bannedUntil: z.string().nullable().optional(),
});

export type BanUserInput = z.infer<typeof banUserSchema>;

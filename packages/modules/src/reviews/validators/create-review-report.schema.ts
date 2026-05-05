import { z } from "zod";

export const createReviewReportSchema = z.object({
    reason: z.enum(["inappropriate", "offensive", "fake", "spam", "other"]),
    description: z.string().max(1000).nullable().optional(),
});

export type CreateReviewReportInput = z.infer<typeof createReviewReportSchema>;

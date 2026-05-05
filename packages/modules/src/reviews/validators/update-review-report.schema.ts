import { z } from "zod";

export const updateReviewReportSchema = z.object({
    status: z.enum(["pending", "reviewed", "rejected", "removed"]),
});

export type UpdateReviewReportInput = z.infer<typeof updateReviewReportSchema>;

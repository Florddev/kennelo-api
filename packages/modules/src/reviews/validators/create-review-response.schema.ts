import { z } from "zod";

export const createReviewResponseSchema = z.object({
    response: z.string().min(1).max(2000),
});

export type CreateReviewResponseInput = z.infer<typeof createReviewResponseSchema>;

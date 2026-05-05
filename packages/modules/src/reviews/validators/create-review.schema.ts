import { z } from "zod";

export const createReviewSchema = z.object({
    overallRating: z.number().int().min(1).max(5),
    comment: z.string().max(2000).nullable().optional(),
    privateFeedback: z.string().max(2000).nullable().optional(),
    wouldRecommend: z.boolean(),
    criteriaScores: z
        .array(
            z.object({
                code: z.string().min(1),
                score: z.number().int().min(1).max(5),
            }),
        )
        .optional(),
});

export type CreateReviewInput = z.infer<typeof createReviewSchema>;

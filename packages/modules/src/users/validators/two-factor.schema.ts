import { z } from "zod";

export const confirmTwoFactorSchema = z.object({
    code: z.string().length(6, "Code must be 6 digits"),
});

export type ConfirmTwoFactorInput = z.infer<typeof confirmTwoFactorSchema>;

export const disableTwoFactorSchema = z.object({
    password: z.string().min(1, "Password is required"),
});

export type DisableTwoFactorInput = z.infer<typeof disableTwoFactorSchema>;

export const twoFactorChallengeSchema = z
    .object({
        code: z.string().optional(),
        recoveryCode: z.string().optional(),
    })
    .refine((data) => Boolean(data.code?.length) || Boolean(data.recoveryCode?.length), {
        message: "A verification code is required",
        path: ["code"],
    });

export type TwoFactorChallengeInput = z.infer<typeof twoFactorChallengeSchema>;

import { z } from "zod";
import { passwordRules } from "./password-rules";

export const changePasswordSchema = z
    .object({
        currentPassword: z.string().min(1, "Current password is required"),
        password: passwordRules,
        passwordConfirmation: z.string().min(1, "Password confirmation is required"),
    })
    .refine((data) => data.password === data.passwordConfirmation, {
        message: "Passwords do not match",
        path: ["passwordConfirmation"],
    });

export type ChangePasswordInput = z.infer<typeof changePasswordSchema>;

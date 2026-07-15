import { z } from "zod";
import { passwordRules } from "./password-rules";

export const resetPasswordSchema = z
    .object({
        token: z.string().min(1, "Token is required"),
        email: z.string().min(1, "Email is required").email("Invalid email address"),
        password: passwordRules,
        passwordConfirmation: z.string().min(1, "Password confirmation is required"),
    })
    .refine((data) => data.password === data.passwordConfirmation, {
        message: "Passwords do not match",
        path: ["passwordConfirmation"],
    });

export type ResetPasswordInput = z.infer<typeof resetPasswordSchema>;

import { z } from "zod";
import { passwordRules } from "./password-rules";

export const renewPasswordSchema = z
    .object({
        password: passwordRules,
        passwordConfirmation: z.string().min(1, "Password confirmation is required"),
    })
    .refine((data) => data.password === data.passwordConfirmation, {
        message: "Passwords do not match",
        path: ["passwordConfirmation"],
    });

export type RenewPasswordInput = z.infer<typeof renewPasswordSchema>;

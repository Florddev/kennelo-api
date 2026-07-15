import { z } from "zod";
import { passwordRules } from "./password-rules";

export const registerUserSchema = z
    .object({
        firstName: z
            .string()
            .min(1, "First name is required")
            .max(255, "First name must be less than 255 characters"),
        lastName: z
            .string()
            .min(1, "Last name is required")
            .max(255, "Last name must be less than 255 characters"),
        email: z
            .string()
            .min(1, "Email is required")
            .email("Invalid email address")
            .max(255, "Email must be less than 255 characters"),
        // phone: z.string().max(20, "Phone number must be less than 20 characters").optional(),
        password: passwordRules,
        passwordConfirmation: z.string().min(1, "Password confirmation is required"),
        locale: z.string().optional(),
    })
    .refine((data) => data.password === data.passwordConfirmation, {
        message: "Passwords do not match",
        path: ["passwordConfirmation"],
    });

export type RegisterUserInput = z.infer<typeof registerUserSchema>;

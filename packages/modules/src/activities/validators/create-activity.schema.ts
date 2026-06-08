import { z } from "zod";

export const ACTIVITY_TYPES = [
    "boarding",
    "breeding",
    "daycare",
    "shelter",
    "other",
    "pet-sitter",
    "home-care",
    "host-family",
    "mobile-boarding",
] as const;

export type ActivityTypeValue = (typeof ACTIVITY_TYPES)[number];

export const addressSchema = z.object({
    line1: z.string().min(1).max(255),
    line2: z.union([z.string().max(255), z.literal("")]).optional(),
    city: z.string().min(1).max(100),
    postalCode: z.string().min(1).max(20),
    region: z.union([z.string().max(100), z.literal("")]).optional(),
    country: z.string().min(1).max(100),
});

export const createActivitySchema = z.object({
    type: z.enum(ACTIVITY_TYPES).optional(),
    name: z.string().min(1).max(255),
    description: z.union([z.string().max(2000), z.literal("")]).optional(),
    phone: z.union([z.string().max(20), z.literal("")]).optional(),
    email: z.union([z.string().email().max(255), z.literal("")]).optional(),
    website: z.union([z.string().url().max(255), z.literal("")]).optional(),
    siret: z.union([z.string().max(14), z.literal("")]).optional(),
    address: addressSchema.optional(),
});

export type CreateActivityInput = z.infer<typeof createActivitySchema>;

import { z } from "zod";

const availabilityStatusEnum = z.enum(["open", "closed"]);

export const createAvailabilitySchema = z.object({
    startDate: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
    endDate: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
    status: availabilityStatusEnum,
    note: z.union([z.string().max(255), z.literal("")]).optional(),
});

export type CreateAvailabilityInput = z.infer<typeof createAvailabilitySchema>;

export const bulkAvailabilitySchema = z.object({
    dates: z
        .array(z.string().regex(/^\d{4}-\d{2}-\d{2}$/))
        .min(1)
        .max(365),
    status: availabilityStatusEnum,
    note: z.union([z.string().max(255), z.literal("")]).optional(),
});

export type BulkAvailabilityInput = z.infer<typeof bulkAvailabilitySchema>;

export const updateAvailabilitySchema = z.object({
    status: availabilityStatusEnum.optional(),
    note: z.union([z.string().max(255), z.literal("")]).optional(),
});

export type UpdateAvailabilityInput = z.infer<typeof updateAvailabilitySchema>;

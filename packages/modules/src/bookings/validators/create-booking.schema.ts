import { z } from "zod";

export const createBookingSchema = z.object({
    activityId: z.string().uuid(),
    checkInDate: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
    checkOutDate: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
    petIds: z.array(z.string().uuid()).min(1),
    serviceIds: z.array(z.string().uuid()).optional(),
    specialRequests: z.union([z.string().max(1000), z.literal("")]).optional(),
    paymentMethodId: z.string().min(1),
    savePaymentMethod: z.boolean().optional(),
});

export type CreateBookingInput = z.infer<typeof createBookingSchema>;

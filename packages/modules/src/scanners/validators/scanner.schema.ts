import { z } from "zod";

export const addScannerSchema = z.object({
    code: z.string().min(1),
    name: z.string().max(100).optional(),
});

export type AddScannerInput = z.infer<typeof addScannerSchema>;

import { z } from "zod";
import { sendableMessageTypes } from "../types/message-type.type";

const messageTypeEnum = z.enum(sendableMessageTypes);

export const sendMessageSchema = z.object({
    content: z.string().max(5000).optional(),
    messageType: messageTypeEnum.optional(),
    bookingId: z.string().uuid().nullable().optional(),
});

export type SendMessageInput = z.infer<typeof sendMessageSchema>;

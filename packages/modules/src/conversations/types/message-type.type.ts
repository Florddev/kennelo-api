export const sendableMessageTypes = ["text", "file", "booking_reference"] as const;

export type SendableMessageType = (typeof sendableMessageTypes)[number];

export type MessageType = SendableMessageType | "system";

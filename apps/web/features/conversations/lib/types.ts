export type ConversationFilter = "all" | "owner" | "host";

export type PendingMessageStatus = "pending" | "failed";

export type PendingMessage = {
    id: string;
    content: string;
    status: PendingMessageStatus;
    createdAt: string;
    files?: File[];
};

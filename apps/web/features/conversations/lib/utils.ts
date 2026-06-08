import type { InfiniteData } from "@tanstack/react-query";
import type { ConversationModel, MessageModel } from "@workspace/modules/conversations";
import { useTranslations } from "next-intl";

export const QUERY_KEYS = {
    personalList: ["conversations", "list", "personal"],
    estList: (estId: string) => ["conversations", "list", "activity", estId],
    allLists: ["conversations", "list"],
    messages: (convId: string | undefined) => ["conversations", "messages", convId],
};

export function sortByLastMessage(a: ConversationModel, b: ConversationModel): number {
    const dateA = a.lastMessageAt ? new Date(a.lastMessageAt).getTime() : 0;
    const dateB = b.lastMessageAt ? new Date(b.lastMessageAt).getTime() : 0;
    return dateB - dateA;
}

export function createTempMessageId(): string {
    if (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function") {
        return `temp-${crypto.randomUUID()}`;
    }
    return `temp-${Date.now()}`;
}

export function replaceConversationLatestMessage(
    conversations: ConversationModel[] | undefined,
    conversationId: string,
    message: MessageModel,
    resetUnread: boolean,
): ConversationModel[] | undefined {
    return conversations?.map((conversation) =>
        conversation.id === conversationId
            ? conversation.withLatestMessage(message, resetUnread)
            : conversation,
    );
}

export function prependMessageToPages(
    prev: InfiniteData<MessageModel[]> | undefined,
    message: MessageModel,
): InfiniteData<MessageModel[]> | undefined {
    if (!prev) return prev;
    if (prev.pages[0]?.some((item) => item.id === message.id)) return prev;
    const [firstPage, ...rest] = prev.pages;
    return { ...prev, pages: [[message, ...(firstPage ?? [])], ...rest] };
}

export function getStatusLabel(
    t: ReturnType<typeof useTranslations>,
    isActivity: boolean,
    isCancelled: boolean,
): string {
    if (!isActivity) return t("features.conversations.bookingReference.requestMade");
    if (isCancelled) return t("features.conversations.bookingReference.refused");
    return t("features.conversations.bookingReference.confirmed");
}

export function computeGrouping(messages: MessageModel[]) {
    return messages.map((msg, i) => {
        const prev = messages[i - 1];
        const next = messages[i + 1];
        const isGroupedWithPrev = !!prev && prev.isGroupedWith(msg);
        const isGroupedWithNext = !!next && msg.isGroupedWith(next);
        return {
            isGrouped: isGroupedWithPrev || isGroupedWithNext,
            isFirstInGroup: !isGroupedWithPrev && isGroupedWithNext,
            isLastInGroup: isGroupedWithPrev && !isGroupedWithNext,
        };
    });
}

export function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

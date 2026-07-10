"use client";

import { useRef, useEffect, useLayoutEffect, Fragment } from "react";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { useConversations } from "@/features/conversations/hooks/use-conversations";
import { MessageItem } from "@/features/conversations/components/message-item";
import { PendingMessageItem } from "./pending-message-item";
import { TypingIndicator } from "./typing-indicator";
import { MessageComposer } from "./message-composer";
import { ConversationHeader } from "./conversation-header";
import { DateSeparator } from "./date-separator";
import { Loader2 } from "lucide-react";
import { computeGrouping } from "../lib/utils";
import { isSameDay } from "@workspace/common";
import { usePlatform } from "@/hooks/use-platform";
import { cn } from "@workspace/ui/lib/utils";

function MessagesSkeleton() {
    return (
        <div className="space-y-3">
            <div className="flex justify-start">
                <Skeleton className="h-10 w-48 rounded-2xl rounded-bl-sm" />
            </div>
            <div className="flex justify-end">
                <Skeleton className="h-10 w-56 rounded-2xl rounded-br-sm" />
            </div>
            <div className="flex justify-start">
                <Skeleton className="h-14 w-64 rounded-2xl rounded-bl-sm" />
            </div>
            <div className="flex justify-end">
                <Skeleton className="h-10 w-40 rounded-2xl rounded-br-sm" />
            </div>
            <div className="flex justify-start">
                <Skeleton className="h-10 w-52 rounded-2xl rounded-bl-sm" />
            </div>
        </div>
    );
}

export function ConversationView({ onBack }: { onBack?: () => void }) {
    const {
        selectedConversation,
        messages,
        isLoadingMessages,
        isLoadingMore,
        hasMoreMessages,
        loadMoreMessages,
        pendingMessages,
        retryMessage,
        dismissFailedMessage,
        typingUser,
    } = useConversations();

    const messagesEndRef = useRef<HTMLDivElement>(null);
    const scrollContainerRef = useRef<HTMLDivElement>(null);
    const prevScrollHeightRef = useRef(0);
    const isNearBottomRef = useRef(true);
    const { isCapacitorApp } = usePlatform();

    useEffect(() => {
        if (isLoadingMore) {
            prevScrollHeightRef.current = scrollContainerRef.current?.scrollHeight ?? 0;
        }
    }, [isLoadingMore]);

    useLayoutEffect(() => {
        if (!isLoadingMore && prevScrollHeightRef.current > 0 && scrollContainerRef.current) {
            scrollContainerRef.current.scrollTop =
                scrollContainerRef.current.scrollHeight - prevScrollHeightRef.current;
            prevScrollHeightRef.current = 0;
        }
    }, [isLoadingMore, messages.length]);

    useEffect(() => {
        if (isNearBottomRef.current) {
            messagesEndRef.current?.scrollIntoView({ behavior: "instant" });
        }
    }, [messages.length, pendingMessages.length, typingUser]);

    if (!selectedConversation) return null;

    const activity = selectedConversation.activity;
    const name = activity?.name ?? selectedConversation.user?.getFullName() ?? "—";
    const receiverAvatarUrl =
        activity?.getAvatarUrl() ?? selectedConversation.user?.avatarUrl ?? "";

    const handleScroll = () => {
        const el = scrollContainerRef.current;
        if (!el) return;
        isNearBottomRef.current = el.scrollHeight - el.scrollTop - el.clientHeight < 100;
        if (el.scrollTop < 50 && hasMoreMessages && !isLoadingMore) loadMoreMessages();
    };

    const grouping = computeGrouping(messages);

    return (
        <div
            data-slot="conversation-view"
            className={cn(
                "flex h-full flex-col overflow-hidden",
                isCapacitorApp && "mt-[var(--mobile-top-margin)]",
            )}
        >
            <ConversationHeader name={name} avatarUrl={receiverAvatarUrl} onBack={onBack} />
            <div className="h-full flex flex-col bg-card">
                <div
                    ref={scrollContainerRef}
                    onScroll={handleScroll}
                    className="flex-1 overflow-y-auto pt-4 px-2 md:px-8"
                >
                    {isLoadingMore && (
                        <div className="flex justify-center py-2">
                            <Loader2 className="size-4 animate-spin text-muted-foreground" />
                        </div>
                    )}
                    {isLoadingMessages ? (
                        <MessagesSkeleton />
                    ) : (
                        <>
                            {messages.map((message, i) => {
                                const prev = messages[i - 1];
                                const showSeparator =
                                    !prev ||
                                    !isSameDay(
                                        new Date(message.createdAt),
                                        new Date(prev.createdAt),
                                    );
                                return (
                                    <Fragment key={message.id}>
                                        {showSeparator && (
                                            <DateSeparator
                                                date={message.createdAt}
                                                displaySeparators={false}
                                            />
                                        )}
                                        <MessageItem
                                            message={message}
                                            isGrouped={grouping[i]!.isGrouped}
                                            isFirstInGroup={grouping[i]!.isFirstInGroup}
                                            isLastInGroup={grouping[i]!.isLastInGroup}
                                        />
                                    </Fragment>
                                );
                            })}
                            {pendingMessages.map((pm) => (
                                <PendingMessageItem
                                    key={pm.id}
                                    content={pm.content}
                                    files={pm.files}
                                    status={pm.status}
                                    onRetry={() => retryMessage(pm.id)}
                                    onDismiss={() => dismissFailedMessage(pm.id)}
                                />
                            ))}
                            {typingUser && <TypingIndicator name={typingUser} />}
                        </>
                    )}
                    <div ref={messagesEndRef} />
                </div>
                <div className="md:px-6">
                    <MessageComposer />
                </div>
            </div>
        </div>
    );
}

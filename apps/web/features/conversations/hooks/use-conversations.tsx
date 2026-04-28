"use client";

import {
    createContext,
    useContext,
    useState,
    useCallback,
    useMemo,
    useEffect,
    useRef,
    type ReactNode,
} from "react";
import {
    useInfiniteQuery,
    useQuery,
    useQueries,
    useQueryClient,
    type InfiniteData,
} from "@tanstack/react-query";
import {
    ConversationModel,
    MessageModel,
    type MessageDto,
    getConversations,
    getEstablishmentConversations,
    getMessages,
    sendMessage as sendMessageAction,
    markConversationMessagesRead,
} from "@workspace/modules/conversations";
import { echoClient } from "@workspace/common";
import { useAuth } from "@/features/auth";
import type { ConversationFilter, PendingMessage } from "../lib/types";
import {
    QUERY_KEYS,
    sortByLastMessage,
    createTempMessageId,
    replaceConversationLatestMessage,
    prependMessageToPages,
} from "../lib/utils";

export type { ConversationFilter, PendingMessage };
export type { PendingMessageStatus } from "../lib/types";

const PER_PAGE = 20;

interface ConversationsContextValue {
    conversations: ConversationModel[];
    filteredConversations: ConversationModel[];
    isLoadingConversations: boolean;
    selectedConversation: ConversationModel | null;
    messages: MessageModel[];
    isLoadingMessages: boolean;
    loadMoreMessages: () => void;
    hasMoreMessages: boolean;
    isLoadingMore: boolean;
    selectConversation: (conversation: ConversationModel) => void;
    closeConversation: () => void;
    sendMessage: (content: string, files?: File[]) => Promise<void>;
    isSending: boolean;
    pendingMessages: PendingMessage[];
    retryMessage: (tempId: string) => Promise<void>;
    dismissFailedMessage: (tempId: string) => void;
    typingUser: string | null;
    notifyTyping: () => void;
    activeFilter: ConversationFilter;
    setActiveFilter: (filter: ConversationFilter) => void;
    searchQuery: string;
    setSearchQuery: (query: string) => void;
}

const ConversationsContext = createContext<ConversationsContextValue | undefined>(undefined);

export function ConversationsProvider({ children }: { children: ReactNode }) {
    const [selectedConversation, setSelectedConversation] = useState<ConversationModel | null>(
        null,
    );
    const [activeFilter, setActiveFilter] = useState<ConversationFilter>("all");
    const [searchQuery, setSearchQuery] = useState("");
    const [pendingMessages, setPendingMessages] = useState<PendingMessage[]>([]);
    const [typingUser, setTypingUser] = useState<string | null>(null);
    const queryClient = useQueryClient();
    const { user, establishments, isLoading: isAuthLoading } = useAuth();
    const conversationChannelRef = useRef<ReturnType<typeof echoClient.private> | null>(null);
    const typingTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const typingThrottleRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const { data: ownerConversations = [], isLoading: isLoadingOwner } = useQuery({
        queryKey: QUERY_KEYS.personalList,
        queryFn: () => getConversations(),
        enabled: !isAuthLoading,
    });

    const hostQueries = useQueries({
        queries: establishments.map((est) => ({
            queryKey: QUERY_KEYS.estList(est.id),
            queryFn: () => getEstablishmentConversations(est.id),
            enabled: !isAuthLoading,
        })),
    });

    const hostConversations = useMemo(
        () => hostQueries.flatMap((q) => q.data ?? []),
        [hostQueries],
    );

    const isLoadingHost = hostQueries.some((q) => q.isLoading);
    const isLoadingConversations = isAuthLoading || isLoadingOwner || isLoadingHost;

    const allConversations = useMemo(() => {
        const seen = new Set<string>();
        return [...ownerConversations, ...hostConversations]
            .filter((c) => {
                if (seen.has(c.id)) return false;
                seen.add(c.id);
                return true;
            })
            .sort(sortByLastMessage);
    }, [ownerConversations, hostConversations]);

    const filteredConversations = useMemo(() => {
        let base: ConversationModel[];
        if (activeFilter === "owner") base = [...ownerConversations].sort(sortByLastMessage);
        else if (activeFilter === "host") base = [...hostConversations].sort(sortByLastMessage);
        else base = allConversations;

        if (!searchQuery.trim()) return base;

        const q = searchQuery.trim().toLowerCase();
        return base.filter((c) => {
            const isOwner = c.userId === user?.id;
            const name = isOwner ? (c.establishment?.name ?? "") : (c.user?.getFullName() ?? "");
            return name.toLowerCase().includes(q);
        });
    }, [activeFilter, ownerConversations, hostConversations, allConversations, searchQuery, user]);

    const {
        data: messagesData,
        isLoading: isLoadingMessages,
        isFetchingNextPage: isLoadingMore,
        hasNextPage,
        fetchNextPage,
    } = useInfiniteQuery({
        queryKey: QUERY_KEYS.messages(selectedConversation?.id),
        queryFn: ({ pageParam }) =>
            getMessages(selectedConversation!.id, { perPage: PER_PAGE, page: pageParam }),
        initialPageParam: 1,
        getNextPageParam: (lastPage, _allPages, lastPageParam) =>
            lastPage.length === PER_PAGE ? lastPageParam + 1 : undefined,
        enabled: !!selectedConversation,
    });

    const messages = useMemo<MessageModel[]>(() => {
        if (!messagesData) return [];
        return [...messagesData.pages.flat()].sort(
            (a, b) => new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime(),
        );
    }, [messagesData]);

    const updateConversationLatestMessage = useCallback(
        (conversationId: string, message: MessageModel, resetUnread = false) => {
            const updater = (prev: ConversationModel[] | undefined) =>
                replaceConversationLatestMessage(prev, conversationId, message, resetUnread);
            queryClient.setQueryData<ConversationModel[]>(QUERY_KEYS.personalList, updater);
            establishments.forEach((est) => {
                queryClient.setQueryData<ConversationModel[]>(QUERY_KEYS.estList(est.id), updater);
            });
        },
        [queryClient, establishments],
    );

    const performSend = useCallback(
        async (pending: PendingMessage, conversationId: string) => {
            try {
                const message = await sendMessageAction(
                    conversationId,
                    { content: pending.content || undefined },
                    pending.files,
                );
                setPendingMessages((prev) => prev.filter((m) => m.id !== pending.id));
                queryClient.setQueryData<InfiniteData<MessageModel[]>>(
                    QUERY_KEYS.messages(conversationId),
                    (prev) => prependMessageToPages(prev, message),
                );
                updateConversationLatestMessage(conversationId, message);
            } catch {
                setPendingMessages((prev) =>
                    prev.map((m) =>
                        m.id === pending.id ? { ...m, status: "failed" as const } : m,
                    ),
                );
            }
        },
        [queryClient, updateConversationLatestMessage],
    );

    const sendMessage = useCallback(
        async (content: string, files?: File[]) => {
            if (!selectedConversation) return;
            const pending: PendingMessage = {
                id: createTempMessageId(),
                content,
                status: "pending",
                createdAt: new Date().toISOString(),
                files,
            };
            setPendingMessages((prev) => [...prev, pending]);
            await performSend(pending, selectedConversation.id);
        },
        [selectedConversation, performSend],
    );

    const retryMessage = useCallback(
        async (tempId: string) => {
            if (!selectedConversation) return;
            const msg = pendingMessages.find((m) => m.id === tempId);
            if (!msg) return;
            const retried: PendingMessage = { ...msg, status: "pending" };
            setPendingMessages((prev) => prev.map((m) => (m.id === tempId ? retried : m)));
            await performSend(retried, selectedConversation.id);
        },
        [selectedConversation, pendingMessages, performSend],
    );

    const dismissFailedMessage = useCallback((tempId: string) => {
        setPendingMessages((prev) => prev.filter((m) => m.id !== tempId));
    }, []);

    const selectConversation = useCallback(
        (conversation: ConversationModel) => {
            setSelectedConversation(conversation);
            setPendingMessages([]);
            queryClient.invalidateQueries({
                queryKey: QUERY_KEYS.messages(conversation.id),
            });
            markConversationMessagesRead(conversation.id)
                .then(() => {
                    queryClient.invalidateQueries({ queryKey: QUERY_KEYS.allLists });
                })
                .catch(() => {});
        },
        [queryClient],
    );

    const closeConversation = useCallback(() => {
        setSelectedConversation(null);
        setPendingMessages([]);
    }, []);

    const notifyTyping = useCallback(() => {
        if (!conversationChannelRef.current || !user || typingThrottleRef.current) return;
        conversationChannelRef.current.whisper("typing", {
            userId: user.id,
            name: user.getFullName(),
        });
        typingThrottleRef.current = setTimeout(() => {
            typingThrottleRef.current = null;
        }, 2000);
    }, [user]);

    useEffect(() => {
        if (!selectedConversation) return;

        const channel = echoClient.private(`conversation.${selectedConversation.id}`);
        conversationChannelRef.current = channel;

        channel.listen(".message.sent", (event: { message: MessageDto }) => {
            const message = MessageModel.from(event.message);
            const isFromCurrentUser = String(message.senderId) === String(user?.id);

            queryClient.setQueryData<InfiniteData<MessageModel[]>>(
                QUERY_KEYS.messages(selectedConversation.id),
                (prev) => prependMessageToPages(prev, message),
            );

            updateConversationLatestMessage(selectedConversation.id, message, !isFromCurrentUser);

            if (!isFromCurrentUser) {
                markConversationMessagesRead(selectedConversation.id).catch(() => {});
            }
        });

        channel.listen(".messages.read", () => {
            queryClient.invalidateQueries({ queryKey: QUERY_KEYS.allLists });
        });

        channel.listenForWhisper("typing", (event: { userId: string; name: string }) => {
            if (event.userId === user?.id) return;
            setTypingUser(event.name);
            if (typingTimeoutRef.current) clearTimeout(typingTimeoutRef.current);
            typingTimeoutRef.current = setTimeout(() => setTypingUser(null), 3000);
        });

        return () => {
            echoClient.leave(`conversation.${selectedConversation.id}`);
            conversationChannelRef.current = null;
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
                typingTimeoutRef.current = null;
            }
            if (typingThrottleRef.current) {
                clearTimeout(typingThrottleRef.current);
                typingThrottleRef.current = null;
            }
            setTypingUser(null);
        };
    }, [selectedConversation, queryClient, updateConversationLatestMessage, user]);

    useEffect(() => {
        if (!user) return;

        const channel = echoClient.private(`user.${user.id}`);

        channel.listen(".new.message", () => {
            queryClient.invalidateQueries({ queryKey: QUERY_KEYS.allLists });
        });

        return () => echoClient.leave(`user.${user.id}`);
    }, [user, queryClient]);

    const isSending = pendingMessages.some((m) => m.status === "pending");

    const value: ConversationsContextValue = {
        conversations: allConversations,
        filteredConversations,
        isLoadingConversations,
        selectedConversation,
        messages,
        isLoadingMessages,
        loadMoreMessages: fetchNextPage,
        hasMoreMessages: hasNextPage ?? false,
        isLoadingMore,
        selectConversation,
        closeConversation,
        sendMessage,
        isSending,
        pendingMessages,
        retryMessage,
        dismissFailedMessage,
        typingUser,
        notifyTyping,
        activeFilter,
        setActiveFilter,
        searchQuery,
        setSearchQuery,
    };

    return <ConversationsContext.Provider value={value}>{children}</ConversationsContext.Provider>;
}

export function useConversations() {
    const context = useContext(ConversationsContext);
    if (!context) throw new Error("useConversations must be used within a ConversationsProvider");
    return context;
}

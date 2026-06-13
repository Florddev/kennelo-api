"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, type ReactNode } from "react";
import { useInfiniteQuery, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { useTranslations } from "next-intl";
import {
    NotificationModel,
    type NotificationDto,
    getNotifications,
    getUnreadNotificationsCount,
    markNotificationRead,
    markAllNotificationsRead,
    deleteNotification as deleteNotificationAction,
} from "@workspace/modules/notifications";
import { echoClient } from "@workspace/common";
import { useAuth } from "@/features/auth";
import { renderNotification } from "../lib/labels";
import { QUERY_KEYS } from "../lib/query-keys";
import {
    markAllNotificationsReadInPages,
    patchNotificationInPages,
    prependNotificationToPages,
    removeNotificationFromPages,
    type NotificationPages,
} from "../lib/notifications-cache";

const PER_PAGE = 20;

interface NotificationsContextValue {
    notifications: NotificationModel[];
    unreadCount: number;
    isLoading: boolean;
    hasMore: boolean;
    loadMore: () => void;
    markAsRead: (id: string) => Promise<void>;
    markAllAsRead: () => Promise<void>;
    remove: (id: string) => Promise<void>;
}

const NotificationsContext = createContext<NotificationsContextValue | undefined>(undefined);

export function NotificationsProvider({ children }: { children: ReactNode }) {
    const queryClient = useQueryClient();
    const { user, isLoading: isAuthLoading } = useAuth();
    const t = useTranslations("features.notifications");

    const translate = useCallback(
        (key: string, values?: Record<string, string>) => t(key as Parameters<typeof t>[0], values),
        [t],
    );

    const {
        data,
        isLoading: isLoadingList,
        hasNextPage,
        fetchNextPage,
    } = useInfiniteQuery({
        queryKey: QUERY_KEYS.list,
        queryFn: ({ pageParam }) => getNotifications({ perPage: PER_PAGE, page: pageParam }),
        initialPageParam: 1,
        getNextPageParam: (last, _all, lastParam) =>
            last.length === PER_PAGE ? lastParam + 1 : undefined,
        enabled: !isAuthLoading && !!user,
    });

    const { data: unreadCount = 0 } = useQuery({
        queryKey: QUERY_KEYS.unreadCount,
        queryFn: getUnreadNotificationsCount,
        enabled: !isAuthLoading && !!user,
    });

    const notifications = useMemo(() => data?.pages.flat() ?? [], [data]);

    const setUnread = useCallback(
        (updater: (n: number) => number) =>
            queryClient.setQueryData<number>(QUERY_KEYS.unreadCount, (prev) => updater(prev ?? 0)),
        [queryClient],
    );

    const invalidateAll = useCallback(() => {
        queryClient.invalidateQueries({ queryKey: QUERY_KEYS.list });
        queryClient.invalidateQueries({ queryKey: QUERY_KEYS.unreadCount });
    }, [queryClient]);

    const markAsRead = useCallback(
        async (id: string) => {
            const target = notifications.find((n) => n.id === id);
            if (!target || target.isRead) return;
            queryClient.setQueryData<NotificationPages>(QUERY_KEYS.list, (prev) =>
                patchNotificationInPages(prev, id, (n) => n.asRead()),
            );
            setUnread((c) => Math.max(0, c - 1));
            try {
                await markNotificationRead(id);
            } catch {
                invalidateAll();
            }
        },
        [notifications, queryClient, setUnread, invalidateAll],
    );

    const markAllAsRead = useCallback(async () => {
        queryClient.setQueryData<NotificationPages>(
            QUERY_KEYS.list,
            markAllNotificationsReadInPages,
        );
        setUnread(() => 0);
        try {
            await markAllNotificationsRead();
        } catch {
            invalidateAll();
        }
    }, [queryClient, setUnread, invalidateAll]);

    const remove = useCallback(
        async (id: string) => {
            const target = notifications.find((n) => n.id === id);
            queryClient.setQueryData<NotificationPages>(QUERY_KEYS.list, (prev) =>
                removeNotificationFromPages(prev, id),
            );
            if (target && !target.isRead) setUnread((c) => Math.max(0, c - 1));
            try {
                await deleteNotificationAction(id);
            } catch {
                invalidateAll();
            }
        },
        [notifications, queryClient, setUnread, invalidateAll],
    );

    useEffect(() => {
        if (!user) return;
        const channel = echoClient.private(`user.${user.id}`);

        channel.listen(".notification.created", (event: NotificationDto) => {
            const model = NotificationModel.from(event);
            queryClient.setQueryData<NotificationPages>(QUERY_KEYS.list, (prev) =>
                prependNotificationToPages(prev, model),
            );
            setUnread((c) => c + 1);

            const { title, body } = renderNotification(model, translate);
            toast(title, { description: body });
        });

        return () => {
            channel.stopListening(".notification.created");
        };
    }, [user, queryClient, setUnread, translate]);

    const value: NotificationsContextValue = {
        notifications,
        unreadCount,
        isLoading: isAuthLoading || isLoadingList,
        hasMore: hasNextPage ?? false,
        loadMore: fetchNextPage,
        markAsRead,
        markAllAsRead,
        remove,
    };

    return <NotificationsContext.Provider value={value}>{children}</NotificationsContext.Provider>;
}

export function useNotifications() {
    const ctx = useContext(NotificationsContext);
    if (!ctx) throw new Error("useNotifications must be used within a NotificationsProvider");
    return ctx;
}

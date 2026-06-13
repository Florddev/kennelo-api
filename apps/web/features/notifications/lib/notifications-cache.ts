import type { InfiniteData } from "@tanstack/react-query";
import type { NotificationModel } from "@workspace/modules/notifications";

export type NotificationPages = InfiniteData<NotificationModel[]>;

export function mapNotificationPages(
    data: NotificationPages | undefined,
    fn: (n: NotificationModel) => NotificationModel,
): NotificationPages | undefined {
    if (!data) return data;
    return { ...data, pages: data.pages.map((page) => page.map(fn)) };
}

export function patchNotificationInPages(
    data: NotificationPages | undefined,
    id: string,
    fn: (n: NotificationModel) => NotificationModel,
): NotificationPages | undefined {
    return mapNotificationPages(data, (n) => (n.id === id ? fn(n) : n));
}

export function markAllNotificationsReadInPages(
    data: NotificationPages | undefined,
): NotificationPages | undefined {
    return mapNotificationPages(data, (n) => n.asRead());
}

export function removeNotificationFromPages(
    data: NotificationPages | undefined,
    id: string,
): NotificationPages | undefined {
    if (!data) return data;
    return { ...data, pages: data.pages.map((page) => page.filter((n) => n.id !== id)) };
}

export function prependNotificationToPages(
    data: NotificationPages | undefined,
    model: NotificationModel,
): NotificationPages | undefined {
    if (!data) return data;
    if (data.pages.some((page) => page.some((n) => n.id === model.id))) return data;
    const [first, ...rest] = data.pages;
    return { ...data, pages: [[model, ...(first ?? [])], ...rest] };
}

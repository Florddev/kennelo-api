"use client";

import { useLocale, useTranslations } from "next-intl";
import { Trash2 } from "lucide-react";
import { formatDateAdaptive } from "@workspace/common";
import type { NotificationModel } from "@workspace/modules/notifications";
import { cn } from "@workspace/ui/lib/utils";
import { useNavigation } from "@/hooks/use-navigation";
import { useNotifications } from "../hooks/use-notifications";
import { renderNotification } from "../lib/labels";

export function NotificationItem({
    notification,
    onNavigate,
}: {
    notification: NotificationModel;
    onNavigate?: () => void;
}) {
    const t = useTranslations("features.notifications");
    const locale = useLocale();
    const { routes, push } = useNavigation();
    const { markAsRead, remove } = useNotifications();

    const rendered = renderNotification(
        notification,
        (key, values) => t(key as Parameters<typeof t>[0], values),
        routes,
    );

    const handleClick = () => {
        if (!notification.isRead) markAsRead(notification.id);
        if (rendered.href) {
            push(rendered.href);
            onNavigate?.();
        }
    };

    const handleDelete = (event: React.MouseEvent) => {
        event.stopPropagation();
        remove(notification.id);
    };

    return (
        <div
            data-slot="notification-item"
            role="button"
            tabIndex={0}
            onClick={handleClick}
            onKeyDown={(event) => {
                if (event.key === "Enter" || event.key === " ") handleClick();
            }}
            className={cn(
                "group flex cursor-pointer items-start gap-3 px-4 py-3 transition-colors hover:bg-muted/40",
                !notification.isRead && "bg-primary/5",
            )}
        >
            <span
                aria-hidden
                className={cn(
                    "mt-1.5 size-2 shrink-0 rounded-full",
                    notification.isRead ? "bg-transparent" : "bg-primary",
                )}
            />
            <div className="min-w-0 flex-1">
                <p
                    className={cn(
                        "truncate text-sm text-foreground",
                        !notification.isRead && "font-semibold",
                    )}
                >
                    {rendered.title}
                </p>
                {rendered.body && (
                    <p className="mt-0.5 line-clamp-2 text-sm text-muted-foreground">
                        {rendered.body}
                    </p>
                )}
                <span className="mt-1 block text-xs text-muted-foreground">
                    {formatDateAdaptive(notification.createdAt, locale)}
                </span>
            </div>
            <button
                type="button"
                aria-label={t("delete")}
                onClick={handleDelete}
                className="shrink-0 rounded-4xl p-1.5 text-muted-foreground opacity-0 transition-opacity hover:bg-muted hover:text-foreground focus-visible:opacity-100 group-hover:opacity-100"
            >
                <Trash2 className="size-4" />
            </button>
        </div>
    );
}

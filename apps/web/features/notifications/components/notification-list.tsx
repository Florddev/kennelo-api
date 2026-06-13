"use client";

import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";
import { useNotifications } from "../hooks/use-notifications";
import { NotificationItem } from "./notification-item";

export function NotificationList({
    onItemClick,
    className,
}: {
    onItemClick?: () => void;
    className?: string;
}) {
    const t = useTranslations("features.notifications");
    const { notifications, isLoading, hasMore, loadMore } = useNotifications();

    if (!isLoading && notifications.length === 0) {
        return (
            <div
                data-slot="notification-list-empty"
                className={cn("px-4 py-10 text-center text-sm text-muted-foreground", className)}
            >
                {t("empty")}
            </div>
        );
    }

    return (
        <div data-slot="notification-list" className={cn("flex flex-col", className)}>
            <ul className="divide-y divide-border">
                {notifications.map((notification) => (
                    <li key={notification.id}>
                        <NotificationItem notification={notification} onNavigate={onItemClick} />
                    </li>
                ))}
            </ul>
            {hasMore && (
                <div className="p-3">
                    <Button variant="ghost" size="sm" className="w-full" onClick={() => loadMore()}>
                        {t("loadMore")}
                    </Button>
                </div>
            )}
        </div>
    );
}

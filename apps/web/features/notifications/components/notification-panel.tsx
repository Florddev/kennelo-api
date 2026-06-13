"use client";

import { useTranslations } from "next-intl";
import { CheckCheck } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { ScrollArea } from "@workspace/ui/components/scroll-area";
import { cn } from "@workspace/ui/lib/utils";
import { useNotifications } from "../hooks/use-notifications";
import { NotificationList } from "./notification-list";

export function NotificationPanel({
    onItemClick,
    className,
}: {
    onItemClick?: () => void;
    className?: string;
}) {
    const t = useTranslations("features.notifications");
    const { unreadCount, markAllAsRead } = useNotifications();

    return (
        <div data-slot="notification-panel" className={cn("flex flex-col", className)}>
            <div className="flex items-center justify-between gap-2 border-b border-border px-4 py-3">
                <p className="text-sm font-semibold text-foreground">{t("title")}</p>
                <Button
                    variant="ghost"
                    size="xs"
                    onClick={() => markAllAsRead()}
                    disabled={unreadCount === 0}
                >
                    <CheckCheck className="size-3.5" />
                    {t("markAllAsRead")}
                </Button>
            </div>
            <ScrollArea className="max-h-[60vh] flex-1">
                <NotificationList onItemClick={onItemClick} />
            </ScrollArea>
        </div>
    );
}

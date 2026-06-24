"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { Bell } from "lucide-react";
import { Popover, PopoverContent, PopoverTrigger } from "@workspace/ui/components/popover";
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from "@workspace/ui/components/sheet";
import { cn } from "@workspace/ui/lib/utils";
import { useIsMobile } from "@/hooks/use-mobile";
import { useNotifications } from "../hooks/use-notifications";
import { NotificationPanel } from "./notification-panel";

export function NotificationBell({ className }: { className?: string }) {
    const t = useTranslations("features.notifications");
    const { unreadCount } = useNotifications();
    const isMobile = useIsMobile();
    const [open, setOpen] = useState(false);

    const trigger = (
        <button
            type="button"
            aria-label={t("title")}
            className={cn(
                "relative inline-flex size-9 items-center justify-center rounded-4xl text-foreground transition-colors hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none",
                className,
            )}
        >
            <Bell className="size-5" />
            {unreadCount > 0 && (
                <span
                    data-slot="notification-bell-badge"
                    className="absolute -top-0.5 -end-0.5 inline-flex min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold leading-4 text-primary-foreground"
                >
                    {unreadCount > 99 ? "99+" : unreadCount}
                </span>
            )}
        </button>
    );

    if (isMobile) {
        return (
            <Sheet open={open} onOpenChange={setOpen}>
                <SheetTrigger asChild>{trigger}</SheetTrigger>
                <SheetContent side="bottom" className="max-h-[85vh] rounded-t-2xl p-0">
                    <SheetTitle className="sr-only">{t("title")}</SheetTitle>
                    <NotificationPanel onItemClick={() => setOpen(false)} />
                </SheetContent>
            </Sheet>
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent align="end" sideOffset={8} className="w-96 p-0">
                <NotificationPanel onItemClick={() => setOpen(false)} />
            </PopoverContent>
        </Popover>
    );
}

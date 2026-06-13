"use client";

import { NotificationPanel } from "@/features/notifications";

export default function NotificationsPage() {
    return (
        <div className="mx-auto w-full max-w-2xl py-4">
            <NotificationPanel className="overflow-hidden rounded-2xl border border-border bg-card" />
        </div>
    );
}

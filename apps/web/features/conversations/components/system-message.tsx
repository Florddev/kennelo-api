"use client";

import { cn } from "@workspace/ui/lib/utils";

export function SystemMessage({
    content,
    isGrouped,
}: {
    content: string | null;
    isGrouped: boolean;
}) {
    return (
        <div
            data-slot="message-item"
            data-type="system"
            className={cn("flex justify-center", isGrouped ? "mt-0.5" : "mt-3")}
        >
            <span className="text-xs text-muted-foreground bg-muted px-3 py-1 rounded-full">
                {content}
            </span>
        </div>
    );
}

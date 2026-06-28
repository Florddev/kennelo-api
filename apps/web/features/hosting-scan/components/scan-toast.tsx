"use client";

import { ScanLine } from "lucide-react";

import { cn } from "@workspace/ui/lib/utils";

export function ScanToastCard({
    title,
    subtitle,
    actionLabel,
    onClick,
}: {
    title: string;
    subtitle: string;
    actionLabel: string;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            data-slot="scan-toast"
            onClick={onClick}
            className={cn(
                "flex w-[20rem] items-center gap-3 rounded-2xl border bg-popover p-3 text-start shadow-lg transition-colors hover:bg-accent",
            )}
        >
            <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                <ScanLine className="size-5" />
            </div>
            <div className="flex min-w-0 flex-1 flex-col">
                <span className="text-sm font-medium">{title}</span>
                <span className="truncate text-xs text-muted-foreground">{subtitle}</span>
            </div>
            <span className="shrink-0 text-xs font-semibold text-primary">{actionLabel}</span>
        </button>
    );
}

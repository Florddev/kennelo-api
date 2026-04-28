"use client";

import { useMemo, useEffect } from "react";
import { Loader2, File } from "lucide-react";
import { useTranslations } from "next-intl";
import { cn } from "@workspace/ui/lib/utils";
import Image from "next/image";
import { formatFileSize } from "../lib/utils";

function PendingFilePreview({ file, status }: { file: File; status: "pending" | "failed" }) {
    const objectUrl = useMemo(() => URL.createObjectURL(file), [file]);

    useEffect(() => URL.revokeObjectURL(objectUrl), [objectUrl]);

    const isFailed = status === "failed";

    if (file.type.startsWith("image/")) {
        return (
            <Image
                src={objectUrl}
                alt={file.name}
                className={cn(
                    "max-h-64 max-w-full rounded-2xl",
                    isFailed ? "opacity-40" : "opacity-70",
                )}
                width={150}
                height={150}
            />
        );
    }

    return (
        <div
            className={cn(
                "flex items-center gap-3 px-3 py-2.5 rounded-2xl text-sm text-primary-foreground",
                isFailed ? "bg-primary/40" : "bg-primary/70",
            )}
        >
            <File size={18} className="shrink-0 opacity-70" />
            <div className="flex min-w-0 flex-1 flex-col">
                <span className="truncate font-medium text-sm">{file.name}</span>
                <span className="text-xs opacity-60">{formatFileSize(file.size)}</span>
            </div>
        </div>
    );
}

interface PendingMessageItemProps {
    content: string;
    files?: File[];
    status: "pending" | "failed";
    onRetry: () => void;
    onDismiss: () => void;
}

export function PendingMessageItem({
    content,
    files,
    status,
    onRetry,
    onDismiss,
}: PendingMessageItemProps) {
    const t = useTranslations();

    return (
        <div data-slot="message-item" data-own="true" className="flex flex-col items-end mt-3">
            <div className="flex max-w-[72%] flex-col items-end gap-1.5">
                {!!content && (
                    <div
                        className={cn(
                            "px-3 py-2 rounded-2xl text-sm text-primary-foreground break-words w-fit",
                            status === "failed" ? "bg-primary/40" : "bg-primary/70",
                        )}
                    >
                        <p className="leading-relaxed">{content}</p>
                    </div>
                )}
                {!!files?.length &&
                    files.map((file, i) => (
                        <PendingFilePreview key={i} file={file} status={status} />
                    ))}
            </div>
            <div className="flex items-center gap-2 mt-1">
                {status === "pending" ? (
                    <Loader2 className="size-3 animate-spin text-muted-foreground" />
                ) : (
                    <>
                        <span className="text-[10px] text-destructive">
                            {t("features.conversations.sendFailed")}
                        </span>
                        <button
                            onClick={onRetry}
                            className="text-[10px] text-primary underline hover:opacity-80"
                        >
                            {t("features.conversations.retry")}
                        </button>
                        <button
                            onClick={onDismiss}
                            className="text-[10px] text-muted-foreground hover:text-foreground leading-none"
                        >
                            ×
                        </button>
                    </>
                )}
            </div>
        </div>
    );
}

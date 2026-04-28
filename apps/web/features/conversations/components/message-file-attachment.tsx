"use client";

import { MessageFileModel } from "@workspace/modules/conversations";
import { cn } from "@workspace/ui/lib/utils";
import { Download, File } from "lucide-react";
import { useTranslations } from "next-intl";
import Image from "next/image";
import { formatFileSize } from "../lib/utils";

export function MessageFileAttachment({ file, isOwn }: { file: MessageFileModel; isOwn: boolean }) {
    const t = useTranslations("common.actions");

    if (file.mimeType.startsWith("image/")) {
        return (
            <a
                href={file.filePath}
                target="_blank"
                rel="noopener noreferrer"
                data-slot="message-image-attachment"
                className="block"
            >
                <Image
                    src={file.filePath}
                    alt={file.fileName}
                    className="max-h-64 max-w-full rounded-2xl"
                    width={150}
                    height={150}
                />
            </a>
        );
    }

    return (
        <a
            href={file.filePath}
            download={file.fileName}
            target="_blank"
            rel="noopener noreferrer"
            data-slot="message-file-attachment"
            className={cn(
                "flex items-center gap-3 px-3 py-2.5 rounded-2xl text-sm no-underline transition-opacity hover:opacity-80",
                isOwn ? "bg-primary text-primary-foreground" : "bg-muted text-foreground",
            )}
        >
            <File size={18} className="shrink-0 opacity-70" />
            <div className="flex min-w-0 flex-1 flex-col">
                <span className="truncate font-medium text-sm">{file.fileName}</span>
                <span className="text-xs opacity-60">{formatFileSize(file.fileSize)}</span>
            </div>
            <Download size={14} className="shrink-0 opacity-70" aria-label={t("download")} />
        </a>
    );
}

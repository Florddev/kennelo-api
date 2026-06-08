"use client";

import type { MessageModel } from "@workspace/modules/conversations";
import { cn } from "@workspace/ui/lib/utils";
import { Dot } from "lucide-react";
import { useLocale, useTranslations } from "next-intl";

function getBubbleClassName(
    isOwn: boolean,
    isGrouped: boolean,
    isFirstInGroup: boolean,
    isLastInGroup: boolean,
): string {
    return cn(
        "px-3 py-2 rounded-2xl text-sm break-words",
        isOwn ? "bg-primary text-primary-foreground" : "bg-muted text-foreground",
        isGrouped &&
            cn(
                isOwn ? "rounded-r-none" : "rounded-l-none",
                isFirstInGroup && (isOwn ? "rounded-tr-2xl" : "rounded-tl-2xl"),
                isLastInGroup && (isOwn ? "rounded-br-2xl" : "rounded-bl-2xl"),
            ),
    );
}

export function MessageSenderHeader({
    message,
    isOwn,
    isGrouped,
    isFirstInGroup,
}: {
    message: MessageModel;
    isOwn: boolean;
    isGrouped: boolean;
    isFirstInGroup: boolean;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const showContent = !isGrouped || isFirstInGroup;

    return (
        <div className="mx-1 text-xs flex text-muted-foreground gap-1.5 items-center max-h-4">
            {showContent && (
                <>
                    {!isOwn && (
                        <p className="flex items-center font-semibold">
                            {message.sender?.getFullName()}
                            {message.senderType === "activity" && (
                                <>
                                    <Dot className="w-2" />
                                    {t("features.activities.host")}
                                </>
                            )}
                        </p>
                    )}
                    {message.formattedTime(locale)}
                </>
            )}
        </div>
    );
}

export function MessageBubble({
    content,
    isOwn,
    isGrouped = false,
    isFirstInGroup = false,
    isLastInGroup = false,
}: {
    content: string;
    isOwn: boolean;
    isGrouped?: boolean;
    isFirstInGroup?: boolean;
    isLastInGroup?: boolean;
}) {
    return (
        <div className={getBubbleClassName(isOwn, isGrouped, isFirstInGroup, isLastInGroup)}>
            <p className="leading-relaxed">{content}</p>
        </div>
    );
}

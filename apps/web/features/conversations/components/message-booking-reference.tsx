"use client";

import type { MessageModel } from "@workspace/modules/conversations";
import { cn } from "@workspace/ui/lib/utils";
import { MessageBubble, MessageSenderHeader } from "./message-parts";
import { BookingReferenceCard } from "./booking-reference-card";

export function MessageBookingReference({
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
    return (
        <div className={cn("flex max-w-[72%] flex-col gap-1", isOwn ? "items-end" : "items-start")}>
            <MessageSenderHeader
                message={message}
                isOwn={isOwn}
                isGrouped={isGrouped}
                isFirstInGroup={isFirstInGroup}
            />
            {!!message.content && <MessageBubble content={message.content} isOwn={isOwn} />}
            <BookingReferenceCard message={message} />
        </div>
    );
}

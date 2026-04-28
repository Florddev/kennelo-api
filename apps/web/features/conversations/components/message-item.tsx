"use client";

import { MessageModel } from "@workspace/modules/conversations";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import { cn } from "@workspace/ui/lib/utils";
import { useAuth } from "@/features/auth";
import { MessageFileAttachment } from "./message-file-attachment";
import { MessageBookingReference } from "./message-booking-reference";
import { MessageBubble, MessageSenderHeader } from "./message-parts";
import { SystemMessage } from "./system-message";

function messageItemClass(
    isOwn: boolean,
    isBookingRef: boolean,
    isGrouped: boolean,
    showEdge: boolean,
): string {
    return cn(
        !isBookingRef && isGrouped && "mt-0",
        (isBookingRef || showEdge) && "mb-4",
        isOwn ? "flex justify-end" : "flex items-end gap-2",
    );
}

function SenderAvatar({
    avatarUrl,
    initials,
    show,
}: {
    avatarUrl: string | null;
    initials: string;
    show: boolean;
}) {
    return (
        <div className="size-6 shrink-0">
            {show && (
                <Avatar size="sm">
                    {avatarUrl && <AvatarImage src={avatarUrl} alt={initials} />}
                    <AvatarFallback className="text-xs">{initials}</AvatarFallback>
                </Avatar>
            )}
        </div>
    );
}

function MessageContent({
    message,
    isOwn,
    isGrouped,
    isFirstInGroup,
    isLastInGroup,
}: {
    message: MessageModel;
    isOwn: boolean;
    isGrouped: boolean;
    isFirstInGroup: boolean;
    isLastInGroup: boolean;
}) {
    return (
        <div
            className={cn("flex max-w-[72%] flex-col gap-0.5", isOwn ? "items-end" : "items-start")}
        >
            <MessageSenderHeader
                message={message}
                isOwn={isOwn}
                isGrouped={isGrouped}
                isFirstInGroup={isFirstInGroup}
            />
            {!!message.content && (
                <MessageBubble
                    content={message.content}
                    isOwn={isOwn}
                    isGrouped={isGrouped}
                    isFirstInGroup={isFirstInGroup}
                    isLastInGroup={isLastInGroup}
                />
            )}
            {!!message.files?.length && (
                <div className={cn("flex w-full flex-col gap-1.5", isOwn && "items-end")}>
                    {message.files.map((file) => (
                        <MessageFileAttachment key={file.id} file={file} isOwn={isOwn} />
                    ))}
                </div>
            )}
        </div>
    );
}

export function MessageItem({
    message,
    isGrouped = false,
    isFirstInGroup = false,
    isLastInGroup = true,
}: {
    message: MessageModel;
    isGrouped?: boolean;
    isFirstInGroup?: boolean;
    isLastInGroup?: boolean;
}) {
    const { user } = useAuth();

    if (message.isSystemMessage()) {
        return <SystemMessage content={message.content} isGrouped={isGrouped} />;
    }

    const isOwn = message.senderId === user?.id;
    const showEdge = !isGrouped || isLastInGroup;
    const isBookingRef = message.messageType === "booking_reference";
    const { avatarUrl, initials } = message.getSenderInfo();

    return (
        <div
            data-slot="message-item"
            data-own={isOwn}
            className={messageItemClass(isOwn, isBookingRef, isGrouped, showEdge)}
        >
            {!isOwn && (
                <SenderAvatar
                    avatarUrl={avatarUrl}
                    initials={initials}
                    show={isBookingRef || showEdge}
                />
            )}
            {isBookingRef ? (
                <MessageBookingReference
                    message={message}
                    isOwn={isOwn}
                    isGrouped={isGrouped}
                    isFirstInGroup={isFirstInGroup}
                />
            ) : (
                <MessageContent
                    message={message}
                    isOwn={isOwn}
                    isGrouped={isGrouped}
                    isFirstInGroup={isFirstInGroup}
                    isLastInGroup={isLastInGroup}
                />
            )}
        </div>
    );
}

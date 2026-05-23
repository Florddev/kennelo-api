"use client";

import { useLocale, useTranslations } from "next-intl";
import { ConversationModel, MessageModel } from "@workspace/modules/conversations";
import { EstablishmentModel } from "@workspace/modules/establishments";
import { Avatar, AvatarFallback, AvatarImage } from "@workspace/ui/components/avatar";
import { Badge } from "@workspace/ui/components/badge";
import { cn } from "@workspace/ui/lib/utils";
import { useAuth, UserAvatar } from "@/features/auth";
import { formatTime } from "@workspace/common";
import { Skeleton } from "@workspace/ui/components/skeleton";
import { BookingStatusLine } from "./booking-status-line";

function getConversationParty(
    conversation: ConversationModel,
    currentUserId: string | undefined,
): { name: string; isEstablishment: boolean } {
    const isOwner = conversation.userId === currentUserId;
    return {
        name: isOwner
            ? (conversation.establishment?.name ?? "—")
            : (conversation.user?.getFullName() ?? "—"),
        isEstablishment: isOwner,
    };
}

function getMessagePreview(
    latestMessage: MessageModel | null | undefined,
    userId: string | undefined,
    youLabel: string,
): string {
    if (!latestMessage?.content) return "";
    return latestMessage.senderId === userId
        ? `${youLabel}: ${latestMessage.content}`
        : latestMessage.content;
}

export function ConversationListItemSkeleton() {
    return (
        <div className="w-full flex items-center gap-3 px-4 py-3 text-start">
            <Skeleton className="size-10 rounded-full" />
            <div className="flex-1 min-w-0">
                <div className="flex justify-between items-center gap-2">
                    <Skeleton className="h-4 w-20" />
                    <Skeleton className="h-3 w-10" />
                </div>
                <div className="flex justify-between items-center gap-2 mt-0.5">
                    <Skeleton className="h-3 w-32" />
                </div>
            </div>
        </div>
    );
}

export function EstablishmentAvatar({ establishment }: { establishment: EstablishmentModel }) {
    const estAvatarUrl = establishment.getAvatarUrl();
    const estInitials = establishment.name.slice(0, 2).toUpperCase();

    return (
        <div className="relative size-12 shrink-0">
            <Avatar size="lg" className="after:rounded-[14px] !size-12">
                {estAvatarUrl && (
                    <AvatarImage
                        src={estAvatarUrl}
                        alt={establishment.name}
                        className="rounded-[14px]"
                    />
                )}
                <AvatarFallback className="rounded-[14px]">{estInitials}</AvatarFallback>
            </Avatar>
            <div className="absolute -bottom-2 -end-1.5 rounded-full ring-2 ring-white overflow-hidden bg-muted flex items-center justify-center">
                <UserAvatar user={establishment.manager} size="sm" className="!size-7" />
            </div>
        </div>
    );
}

export function ConversationListItem({
    conversation,
    isSelected,
    onClick,
}: {
    conversation: ConversationModel;
    isSelected: boolean;
    onClick: () => void;
}) {
    const { user } = useAuth();
    const t = useTranslations();
    const locale = useLocale();

    const { name, isEstablishment } = getConversationParty(conversation, user?.id);
    const preview = getMessagePreview(
        conversation.latestMessage,
        user?.id,
        t("features.conversations.you"),
    );
    const timeStr = formatTime(conversation.lastMessageAt, locale);
    const unreadCount = conversation.unreadCount ?? 0;
    const isUnread = unreadCount > 0;

    return (
        <button
            data-slot="conversation-list-item"
            className={cn(
                "w-full flex items-center gap-3 px-4 py-2.5 md:py-4 text-start hover:bg-muted/50 transition-colors rounded-lg",
                isSelected && "bg-muted",
            )}
            onClick={onClick}
        >
            {isEstablishment && conversation.establishment ? (
                <EstablishmentAvatar establishment={conversation.establishment} />
            ) : (
                <UserAvatar user={conversation.user} size="lg" />
            )}
            <div className="flex-1 min-w-0">
                <div className="flex justify-between items-center gap-2">
                    <span
                        className={cn(
                            "text-sm truncate",
                            isUnread ? "font-semibold" : "font-medium",
                        )}
                    >
                        {name}
                    </span>
                    <span className="text-xs text-muted-foreground flex-shrink-0">{timeStr}</span>
                </div>
                <div className="flex justify-between items-center gap-2 mt-0.5">
                    <span
                        className={cn(
                            "text-xs truncate",
                            isUnread ? "text-foreground font-medium" : "text-muted-foreground",
                        )}
                    >
                        {preview}
                    </span>
                    {isUnread && (
                        <Badge
                            size="xs"
                            className="flex-shrink-0 rounded-full min-w-4 justify-center"
                        >
                            {unreadCount}
                        </Badge>
                    )}
                </div>
                <BookingStatusLine bookingThreads={conversation.bookingThreads} />
            </div>
        </button>
    );
}

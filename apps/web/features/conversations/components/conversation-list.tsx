"use client";

import { useTranslations } from "next-intl";
import { KMessage } from "@workspace/ui/icons";
import {
    Empty,
    EmptyHeader,
    EmptyTitle,
    EmptyDescription,
    EmptyMedia,
} from "@workspace/ui/components/empty";
import { useConversations } from "@/features/conversations/hooks/use-conversations";
import {
    ConversationListItem,
    ConversationListItemSkeleton,
} from "@/features/conversations/components/conversation-list-item";

export function ConversationList() {
    const t = useTranslations();
    const {
        filteredConversations,
        isLoadingConversations,
        selectedConversation,
        selectConversation,
    } = useConversations();

    if (isLoadingConversations) {
        return (
            <div className="space-y-0">
                {Array.from({ length: 6 }).map((_, i) => (
                    <ConversationListItemSkeleton key={i} />
                ))}
            </div>
        );
    }

    if (filteredConversations.length === 0) {
        return (
            <Empty className="border-none">
                <EmptyMedia>
                    <KMessage
                        className="size-14"
                        filled
                        secondaryOpacity={0.4}
                        secondary="text-secondary"
                    />
                </EmptyMedia>
                <EmptyHeader>
                    <EmptyTitle>{t("features.conversations.noConversations")}</EmptyTitle>
                    <EmptyDescription>
                        {t("features.conversations.noConversationsDescription")}
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <div data-slot="conversation-list" className="flex flex-col gap-0.5 py-1">
            {filteredConversations.map((conversation) => (
                <ConversationListItem
                    key={conversation.id}
                    conversation={conversation}
                    isSelected={selectedConversation?.id === conversation.id}
                    onClick={() => selectConversation(conversation)}
                />
            ))}
        </div>
    );
}

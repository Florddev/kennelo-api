"use client";

import { useState, useEffect } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { useTranslations } from "next-intl";
import { Badge } from "@workspace/ui/components/badge";
import { Button } from "@workspace/ui/components/button";
import { InputGroup, InputGroupAddon, InputGroupInput } from "@workspace/ui/components/input-group";
import { cn } from "@workspace/ui/lib/utils";
import PageLayout from "@/components/layouts/page-layout";
import { useAuth } from "@/features/auth";
import {
    ConversationsProvider,
    useConversations,
    type ConversationFilter,
} from "@/features/conversations/hooks/use-conversations";
import { ConversationList } from "@/features/conversations/components/conversation-list";
import { ConversationView } from "@/features/conversations/components/conversation-view";
import { Separator } from "@workspace/ui/components/separator";
import { ChatRoundLine, MinimalisticMagnifier } from "@solar-icons/react";
import { routes } from "@/lib/routes";
import Link from "next/link";

function ConversationPanels({
    selectedConversation,
    onBack,
}: Pick<ReturnType<typeof useConversations>, "selectedConversation"> & {
    onBack: () => void;
}) {
    return (
        <>
            <div
                className={cn(
                    "flex h-full overflow-hidden w-full",
                    selectedConversation ? "hidden md:flex" : "flex",
                )}
            >
                <div className="flex flex-col !w-full md:w-80 flex-shrink-0 overflow-y-auto pb-13 md:pb-0">
                    <ConversationList />
                </div>
            </div>

            <div
                className={cn(
                    "fixed inset-0 z-50 flex flex-col transition-transform duration-300 md:hidden",
                    selectedConversation ? "translate-x-0" : "translate-x-full",
                )}
            >
                <ConversationView key={selectedConversation?.id} onBack={onBack} />
            </div>
        </>
    );
}

function MessagePageContent() {
    const t = useTranslations();
    const { user, isAuthenticated } = useAuth();
    const router = useRouter();
    const [hideTitle, setHideTitle] = useState(false);
    const [isSearching, setIsSearching] = useState(false);
    const searchParams = useSearchParams();
    const targetConversationId = searchParams.get("conversation_id");
    const {
        conversations,
        selectedConversation,
        closeConversation,
        selectConversation,
        activeFilter,
        setActiveFilter,
        searchQuery,
        setSearchQuery,
        isLoadingConversations,
    } = useConversations();

    const handleBack = targetConversationId
        ? () => {
              closeConversation();
              router.back();
          }
        : closeConversation;

    useEffect(() => {
        if (!targetConversationId || selectedConversation || isLoadingConversations) return;
        const target = conversations.find((c) => c.id === targetConversationId);
        if (target) selectConversation(target);
    }, [
        targetConversationId,
        conversations,
        selectedConversation,
        isLoadingConversations,
        selectConversation,
    ]);

    const handleSearchOpen = () => {
        setIsSearching(true);
        setHideTitle(true);
    };

    const handleSearchClose = () => {
        setIsSearching(false);
        setHideTitle(false);
        setSearchQuery("");
    };

    const canFilterByHost = user?.hasAnyRoles(["admin", "manager"]);

    const filters: { value: ConversationFilter; label: string }[] = [
        { value: "all", label: t("features.conversations.filters.all") },
        { value: "owner", label: t("features.conversations.filters.owner") },
        ...(canFilterByHost
            ? [
                  {
                      value: "host" as ConversationFilter,
                      label: t("features.conversations.filters.host"),
                  },
              ]
            : []),
    ];

    return (
        <div className="flex w-full justify-between h-[calc(100dvh-var(--header-height))]">
            <div className="w-full md:max-w-96 p-4 md:p-6">
                <PageLayout
                    title={t("features.conversations.title")}
                    Icon={ChatRoundLine}
                    headerTopClassName={cn(isSearching && "w-full")}
                    hideTitle={hideTitle}
                    className="p-0 space-y-0 overflow-hidden"
                    headerTop={
                        isAuthenticated && (
                            <>
                                <div
                                    className={cn(
                                        "flex justify-end",
                                        isSearching &&
                                            "md:w-[calc(100%-4.5rem)] md:absolute md:left-0 md:px-4",
                                    )}
                                >
                                    <InputGroup
                                        className={cn(
                                            "h-7 gap-1 w-full transition-all duration-300 border-none bg-muted has-[[data-slot=input-group-control]:focus-visible]:ring-[2px]",
                                            !isSearching && "size-8",
                                        )}
                                        onClick={!isSearching ? handleSearchOpen : undefined}
                                        autoFocus={isSearching}
                                    >
                                        <InputGroupInput
                                            id="inline-start-input"
                                            placeholder={t("common.actions.search")}
                                            className="placeholder:text-sm"
                                            value={searchQuery}
                                            onChange={(e) => setSearchQuery(e.target.value)}
                                        />
                                        <InputGroupAddon
                                            align="inline-start"
                                            className={cn("transition-all", !isSearching && "pl-2")}
                                        >
                                            <MinimalisticMagnifier className="size-3.5 text-primary" />
                                        </InputGroupAddon>
                                    </InputGroup>
                                </div>
                                {isSearching ? (
                                    <Button
                                        className={cn(
                                            "gap-2",
                                            isSearching && "md:absolute md:right-0",
                                        )}
                                        variant="ghost"
                                        size={"sm"}
                                        onClick={handleSearchClose}
                                    >
                                        {t("common.actions.cancel")}
                                    </Button>
                                ) : null}
                            </>
                        )
                    }
                    headerBottom={
                        isAuthenticated && (
                            <div className="flex gap-1.5">
                                {filters.map((filter) => (
                                    <Badge
                                        key={filter.value}
                                        variant={activeFilter === filter.value ? "default" : "flat"}
                                        size="lg"
                                        className="text-xs cursor-pointer"
                                        onClick={() => setActiveFilter(filter.value)}
                                    >
                                        {filter.label}
                                    </Badge>
                                ))}
                            </div>
                        )
                    }
                >
                    {!isAuthenticated ? (
                        <div className="flex flex-col gap-4 p-4 text-sm">
                            <div className="flex flex-col gap-1">
                                <p className="text-lg text-primary font-semibold">
                                    {t("features.conversations.please-login")}
                                </p>
                                <span className="text-muted-foreground">
                                    {t("features.conversations.please-login-description")}
                                </span>
                            </div>
                            <Button variant="default" className="w-fit px-5" asChild>
                                <Link href={routes.Login()}>{t("common.actions.login")}</Link>
                            </Button>
                        </div>
                    ) : (
                        <ConversationPanels
                            selectedConversation={selectedConversation}
                            onBack={handleBack}
                        />
                    )}
                </PageLayout>
            </div>

            <Separator orientation="vertical" className="hidden md:block w-[1px] h-full" />

            <div className="hidden md:flex flex-1 flex-col h-full w-full">
                {selectedConversation && <ConversationView key={selectedConversation.id} />}
            </div>
        </div>
    );
}

export default function MessagesPage() {
    const searchParams = useSearchParams();
    const initialConversationId = searchParams.get("conversation");

    return (
        <ConversationsProvider initialConversationId={initialConversationId}>
            <MessagePageContent />
        </ConversationsProvider>
    );
}

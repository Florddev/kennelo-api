"use client";

import type { ComponentProps } from "react";
import { useTranslations } from "next-intl";
import { ChatRoundLine } from "@solar-icons/react";

import { openHostBookingConversation } from "@workspace/modules/conversations";
import { Button } from "@workspace/ui/components/button";

import { useAsyncState } from "@/hooks/use-async-state";
import { useNavigation } from "@/hooks/use-navigation";

export function ContactOwnerButton({
    bookingId,
    size,
    className,
}: {
    bookingId: string;
    size?: ComponentProps<typeof Button>["size"];
    className?: string;
}) {
    const t = useTranslations("features.hosting-scan.detail");
    const { push, routes } = useNavigation();
    const { execute, isLoading } = useAsyncState();

    const handleClick = () => {
        execute(() => openHostBookingConversation(bookingId), {
            displayError: true,
            onSuccess: (conversation) => {
                push(routes.HostingMessages({ search_params: { conversation: conversation.id } }));
            },
        });
    };

    return (
        <Button size={size} className={className} disabled={isLoading} onClick={handleClick}>
            <ChatRoundLine />
            {t("contact")}
        </Button>
    );
}

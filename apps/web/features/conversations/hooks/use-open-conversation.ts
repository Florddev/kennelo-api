"use client";

import { useState } from "react";
import {
    createActivityConversation,
    createBookingConversation,
} from "@workspace/modules/conversations";
import { useNavigation } from "@/hooks/use-navigation";

export function useOpenConversation() {
    const { router, routes } = useNavigation();
    const [isPending, setIsPending] = useState(false);

    const openWithActivity = async (activityId: string, userId?: string) => {
        setIsPending(true);
        try {
            const conversation = await createActivityConversation(activityId, userId);
            router.push(routes.Messages({ search_params: { conversation_id: conversation.id } }));
        } finally {
            setIsPending(false);
        }
    };

    const openWithBooking = async (bookingId: string) => {
        setIsPending(true);
        try {
            const conversation = await createBookingConversation(bookingId);
            router.push(routes.Messages({ search_params: { conversation_id: conversation.id } }));
        } finally {
            setIsPending(false);
        }
    };

    return { openWithActivity, openWithBooking, isPending };
}

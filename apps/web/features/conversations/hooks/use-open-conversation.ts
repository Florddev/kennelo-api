"use client";

import { useState } from "react";
import {
    createActivityConversation,
    createBookingConversation,
    createPetConversation,
} from "@workspace/modules/conversations";
import { useNavigation } from "@/hooks/use-navigation";

export function useOpenConversation() {
    const { router, routes } = useNavigation();
    const [isPending, setIsPending] = useState(false);

    const openWithActivity = async (activityId: string) => {
        setIsPending(true);
        try {
            const conversation = await createActivityConversation(activityId);
            router.push(routes.Messages({ search_params: { conversation_id: conversation.id } }));
        } finally {
            setIsPending(false);
        }
    };

    const openWithPetOwner = async (petId: string, activityId?: string) => {
        setIsPending(true);
        try {
            const conversation = await createPetConversation(petId, activityId);
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

    return { openWithActivity, openWithPetOwner, openWithBooking, isPending };
}

"use client";

import { useQuery, useQueryClient } from "@tanstack/react-query";

import {
    getActivitySubscription,
    getPlans,
    getSubscriptionInvoices,
} from "@workspace/modules/subscriptions";

export function subscriptionQueryKey(activityId: string) {
    return ["subscription", activityId] as const;
}

export function useActivitySubscription(activityId: string | null) {
    const queryClient = useQueryClient();

    const subscription = useQuery({
        queryKey: subscriptionQueryKey(activityId ?? ""),
        queryFn: () => getActivitySubscription(activityId as string),
        enabled: activityId !== null,
    });

    const plans = useQuery({
        queryKey: ["subscription-plans"],
        queryFn: getPlans,
    });

    const invoices = useQuery({
        queryKey: ["subscription-invoices", activityId],
        queryFn: () => getSubscriptionInvoices(activityId as string),
        enabled: activityId !== null,
    });

    const refresh = () => {
        if (activityId !== null) {
            queryClient.invalidateQueries({ queryKey: subscriptionQueryKey(activityId) });
            queryClient.invalidateQueries({ queryKey: ["subscription-invoices", activityId] });
        }
    };

    return {
        subscription: subscription.data ?? null,
        plans: plans.data ?? [],
        invoices: invoices.data ?? [],
        isLoading: subscription.isLoading || plans.isLoading,
        isInvoicesLoading: invoices.isLoading,
        error: subscription.error,
        refresh,
    };
}

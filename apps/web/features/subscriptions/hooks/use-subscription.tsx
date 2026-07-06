"use client";

import { useQuery, useQueryClient } from "@tanstack/react-query";

import {
    getPlans,
    getSubscription,
    getSubscriptionInvoices,
} from "@workspace/modules/subscriptions";

export const SUBSCRIPTION_QUERY_KEY = ["subscription"] as const;
export const SUBSCRIPTION_INVOICES_QUERY_KEY = ["subscription-invoices"] as const;
export const SUBSCRIPTION_PLANS_QUERY_KEY = ["subscription-plans"] as const;

export function useSubscription() {
    const queryClient = useQueryClient();

    const subscription = useQuery({
        queryKey: SUBSCRIPTION_QUERY_KEY,
        queryFn: getSubscription,
    });

    const plans = useQuery({
        queryKey: SUBSCRIPTION_PLANS_QUERY_KEY,
        queryFn: getPlans,
    });

    const invoices = useQuery({
        queryKey: SUBSCRIPTION_INVOICES_QUERY_KEY,
        queryFn: getSubscriptionInvoices,
    });

    const refresh = () => {
        queryClient.invalidateQueries({ queryKey: SUBSCRIPTION_QUERY_KEY });
        queryClient.invalidateQueries({ queryKey: SUBSCRIPTION_INVOICES_QUERY_KEY });
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

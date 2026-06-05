"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
    getPaymentMethods,
    setDefaultPaymentMethod,
    deletePaymentMethod,
} from "@workspace/modules/payment-methods";

const QUERY_KEY = ["payment-methods"] as const;

export function usePaymentMethods() {
    const queryClient = useQueryClient();

    const list = useQuery({
        queryKey: QUERY_KEY,
        queryFn: getPaymentMethods,
    });

    const setDefault = useMutation({
        mutationFn: (id: string) => setDefaultPaymentMethod(id),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
    });

    const remove = useMutation({
        mutationFn: (id: string) => deletePaymentMethod(id),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: QUERY_KEY }),
    });

    return {
        paymentMethods: list.data ?? [],
        isLoading: list.isLoading,
        error: list.error,
        refetch: list.refetch,
        setDefault: setDefault.mutateAsync,
        isSettingDefault: setDefault.isPending,
        remove: remove.mutateAsync,
        isRemoving: remove.isPending,
    };
}

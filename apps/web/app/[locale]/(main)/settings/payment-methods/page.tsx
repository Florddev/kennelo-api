"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import { Plus } from "lucide-react";

import { Button } from "@workspace/ui/components/button";

import { usePaymentMethods } from "@/features/payment-methods/hooks/use-payment-methods";
import { PaymentMethodCard } from "@/features/payment-methods/components/payment-method-card";
import { AddPaymentMethodDialog } from "@/features/payment-methods/components/add-payment-method-dialog";

export default function PaymentMethodsPage() {
    const t = useTranslations();
    const { paymentMethods, isLoading, refetch, setDefault, isSettingDefault, remove, isRemoving } =
        usePaymentMethods();
    const [isAddOpen, setIsAddOpen] = useState(false);

    const isUpdating = isSettingDefault || isRemoving;

    const handleSetDefault = async (id: string) => {
        try {
            await setDefault(id);
            toast.success(t("features.payment-methods.defaultUpdated"));
        } catch (error) {
            toast.error(t("features.payment-methods.defaultFailed"), {
                description: error instanceof Error ? error.message : undefined,
            });
        }
    };

    const handleDelete = async (id: string) => {
        try {
            await remove(id);
            toast.success(t("features.payment-methods.deleted"));
        } catch (error) {
            toast.error(t("features.payment-methods.deleteFailed"), {
                description: error instanceof Error ? error.message : undefined,
            });
        }
    };

    return (
        <div className="flex flex-col gap-6">
            <section className="grid gap-4">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h2 className="hidden md:block text-lg font-semibold">
                            {t("features.payment-methods.title")}
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {t("features.payment-methods.description")}
                        </p>
                    </div>
                    <Button onClick={() => setIsAddOpen(true)} className="rounded-4xl shrink-0">
                        <Plus className="size-4 me-2" />
                        {t("features.payment-methods.addCard")}
                    </Button>
                </div>

                {isLoading && (
                    <div className="flex flex-col gap-3">
                        <div className="h-20 animate-pulse rounded-2xl bg-muted" />
                        <div className="h-20 animate-pulse rounded-2xl bg-muted" />
                    </div>
                )}
                {!isLoading && paymentMethods.length === 0 && (
                    <div className="rounded-2xl border border-dashed p-8 text-center">
                        <p className="text-sm text-muted-foreground">
                            {t("features.payment-methods.empty")}
                        </p>
                    </div>
                )}
                {!isLoading && paymentMethods.length > 0 && (
                    <div className="flex flex-col gap-3">
                        {paymentMethods.map((paymentMethod) => (
                            <PaymentMethodCard
                                key={paymentMethod.id}
                                paymentMethod={paymentMethod}
                                onSetDefault={() => handleSetDefault(paymentMethod.id)}
                                onDelete={() => handleDelete(paymentMethod.id)}
                                isUpdating={isUpdating}
                            />
                        ))}
                    </div>
                )}
            </section>

            <AddPaymentMethodDialog
                open={isAddOpen}
                onOpenChange={setIsAddOpen}
                onSuccess={() => {
                    setIsAddOpen(false);
                    refetch();
                    toast.success(t("features.payment-methods.saved"));
                }}
            />
        </div>
    );
}

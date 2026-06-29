"use client";

import { useEffect, useState } from "react";
import { useTranslations } from "next-intl";
import { Check, CreditCard, Plus } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent } from "@workspace/ui/components/card";
import { cn } from "@workspace/ui/lib/utils";

import { usePaymentMethods } from "@/features/payment-methods/hooks/use-payment-methods";
import { AddPaymentMethodDialog } from "./add-payment-method-dialog";

type PaymentMethodPickerProps = {
    value: string | null;
    onChange: (paymentMethodId: string) => void;
    className?: string;
};

export function PaymentMethodPicker({ value, onChange, className }: PaymentMethodPickerProps) {
    const t = useTranslations();
    const { paymentMethods, isLoading, refetch } = usePaymentMethods();
    const [isAddOpen, setIsAddOpen] = useState(false);

    useEffect(() => {
        if (value || paymentMethods.length === 0) return;
        const defaultPm = paymentMethods.find((p) => p.isDefault) ?? paymentMethods[0];
        if (defaultPm) onChange(defaultPm.id);
    }, [value, paymentMethods, onChange]);

    return (
        <section data-slot="payment-method-picker" className={cn("flex flex-col gap-3", className)}>
            <div className="flex items-center justify-between">
                <h3 className="text-sm font-semibold text-foreground">
                    {t("features.bookings.checkout.paymentMethodLabel")}
                </h3>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => setIsAddOpen(true)}
                    className="gap-1.5 rounded-full"
                >
                    <Plus className="size-4" />
                    {t("features.payment-methods.addCard")}
                </Button>
            </div>

            {isLoading && <div className="h-16 animate-pulse rounded-2xl bg-muted" />}
            {!isLoading && paymentMethods.length === 0 && (
                <Card className="rounded-2xl border-dashed">
                    <CardContent className="flex flex-col items-center gap-2 px-4 py-6 text-center">
                        <CreditCard className="size-6 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">
                            {t("features.bookings.checkout.noPaymentMethod")}
                        </p>
                        <Button
                            type="button"
                            onClick={() => setIsAddOpen(true)}
                            className="rounded-4xl"
                        >
                            {t("features.payment-methods.addCardCta")}
                        </Button>
                    </CardContent>
                </Card>
            )}
            {!isLoading && paymentMethods.length > 0 && (
                <div className="flex flex-col gap-2">
                    {paymentMethods.map((pm) => {
                        const expiry = `${String(pm.expMonth).padStart(2, "0")}/${String(pm.expYear).slice(-2)}`;
                        const brandLabel = pm.brand
                            ? pm.brand.charAt(0).toUpperCase() + pm.brand.slice(1)
                            : "Card";
                        const isSelected = value === pm.id;
                        return (
                            <button
                                type="button"
                                key={pm.id}
                                onClick={() => onChange(pm.id)}
                                aria-pressed={isSelected}
                                className={cn(
                                    "flex items-center gap-3 rounded-2xl border bg-card px-4 py-3 text-start transition",
                                    isSelected
                                        ? "border-primary ring-2 ring-primary/30"
                                        : "border-border hover:border-foreground/40",
                                )}
                            >
                                <span
                                    className={cn(
                                        "flex size-5 shrink-0 items-center justify-center rounded-full border-2 transition",
                                        isSelected
                                            ? "border-primary bg-primary text-primary-foreground"
                                            : "border-muted-foreground/40",
                                    )}
                                >
                                    {isSelected && <Check className="size-3" strokeWidth={3} />}
                                </span>
                                <CreditCard className="size-5 text-muted-foreground" />
                                <span className="flex-1 text-sm font-medium">
                                    {brandLabel} •••• {pm.last4}
                                </span>
                                <span className="text-xs text-muted-foreground">{expiry}</span>
                            </button>
                        );
                    })}
                </div>
            )}

            <AddPaymentMethodDialog
                open={isAddOpen}
                onOpenChange={setIsAddOpen}
                onSuccess={() => {
                    setIsAddOpen(false);
                    refetch();
                }}
            />
        </section>
    );
}

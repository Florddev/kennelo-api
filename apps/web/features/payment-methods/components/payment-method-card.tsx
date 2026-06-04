"use client";

import { useTranslations } from "next-intl";
import { CreditCard, Star, Trash2 } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent } from "@workspace/ui/components/card";
import { Badge } from "@workspace/ui/components/badge";
import { cn } from "@workspace/ui/lib/utils";
import type { PaymentMethodModel } from "@workspace/modules/payment-methods";

type PaymentMethodCardProps = {
    paymentMethod: PaymentMethodModel;
    onSetDefault: () => void;
    onDelete: () => void;
    isUpdating: boolean;
    className?: string;
};

export function PaymentMethodCard({
    paymentMethod,
    onSetDefault,
    onDelete,
    isUpdating,
    className,
}: PaymentMethodCardProps) {
    const t = useTranslations();
    const brandLabel = paymentMethod.brand
        ? paymentMethod.brand.charAt(0).toUpperCase() + paymentMethod.brand.slice(1)
        : t("features.payment-methods.unknownBrand");
    const expiry = `${String(paymentMethod.expMonth).padStart(2, "0")}/${String(paymentMethod.expYear).slice(-2)}`;

    return (
        <Card data-slot="payment-method-card" className={cn("rounded-2xl border", className)}>
            <CardContent className="flex items-center gap-3 px-4 py-3">
                <div className="flex size-10 items-center justify-center rounded-2xl bg-muted">
                    <CreditCard className="size-5 text-muted-foreground" />
                </div>
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                        <p className="text-sm font-medium text-foreground">
                            {brandLabel} •••• {paymentMethod.last4}
                        </p>
                        {paymentMethod.isDefault && (
                            <Badge variant="default" className="text-[10px]">
                                {t("features.payment-methods.default")}
                            </Badge>
                        )}
                    </div>
                    {paymentMethod.cardholderName && (
                        <p className="text-xs text-foreground mt-0.5 truncate">
                            {paymentMethod.cardholderName}
                        </p>
                    )}
                    <p className="text-xs text-muted-foreground mt-0.5">
                        {t("features.payment-methods.expires", { value: expiry })}
                    </p>
                </div>
                <div className="flex items-center gap-1">
                    {!paymentMethod.isDefault && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={onSetDefault}
                            disabled={isUpdating}
                            aria-label={t("features.payment-methods.makeDefault")}
                            className="rounded-full"
                        >
                            <Star className="size-4" />
                        </Button>
                    )}
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={onDelete}
                        disabled={isUpdating}
                        aria-label={t("common.actions.delete")}
                        className="rounded-full text-destructive hover:text-destructive"
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

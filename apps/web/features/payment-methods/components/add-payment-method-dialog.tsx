"use client";

import { useCallback, useEffect, useMemo, useState } from "react";

import { useTranslations } from "next-intl";
import { toast } from "sonner";

import {
    Elements,
    CardNumberElement,
    CardExpiryElement,
    CardCvcElement,
    useStripe,
    useElements,
} from "@stripe/react-stripe-js";
import type {
    StripeCardNumberElementChangeEvent,
    StripeCardExpiryElementChangeEvent,
    StripeCardCvcElementChangeEvent,
} from "@stripe/stripe-js";

import { Button } from "@workspace/ui/components/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@workspace/ui/components/dialog";
import { Input } from "@workspace/ui/components/input";
import { cn } from "@workspace/ui/lib/utils";
import { createPaymentMethodSetupIntent } from "@workspace/modules/payment-methods";

import { getStripe } from "@/lib/stripe";

type AddPaymentMethodDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSuccess?: () => void;
};

type CardFormProps = {
    onSuccess?: () => void;
    onCancel: () => void;
    clientSecret: string;
};

type StripeFieldWrapperProps = {
    label: string;
    error?: string;
    children: React.ReactNode;
};

type StripeElementChangeEvent =
    | StripeCardNumberElementChangeEvent
    | StripeCardExpiryElementChangeEvent
    | StripeCardCvcElementChangeEvent;

const stripePromise = getStripe();

const elementOptions = {
    style: {
        base: {
            fontSize: "15px",
            color: "#0f172a",
            fontFamily: "var(--font-sans), system-ui, sans-serif",
            "::placeholder": { color: "#94a3b8" },
            iconColor: "#0f172a",
        },
        invalid: {
            color: "#dc2626",
            iconColor: "#dc2626",
        },
    },
    showIcon: true,
};

function StripeFieldWrapper({ label, error, children }: StripeFieldWrapperProps) {
    return (
        <div data-slot="stripe-field" className="flex flex-col">
            <label className="text-xs font-medium text-muted-foreground mb-1.5 block">
                {label}
            </label>
            <div
                className={cn(
                    "rounded-2xl border bg-background px-4 py-3 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20",
                    error &&
                        "border-destructive focus-within:border-destructive focus-within:ring-destructive/20",
                )}
            >
                {children}
            </div>
            {error && <p className="text-xs text-destructive mt-1.5">{error}</p>}
        </div>
    );
}

function CardForm({ onSuccess, onCancel, clientSecret }: CardFormProps) {
    const t = useTranslations();
    const stripe = useStripe();
    const elements = useElements();

    const [cardholderName, setCardholderName] = useState("");
    const [isSaving, setIsSaving] = useState(false);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

    const isReady = Boolean(stripe && elements);

    const handleElementChange = useCallback(
        (key: string) => (event: StripeElementChangeEvent) => {
            setFieldErrors((previous) => {
                const next = { ...previous };
                if (event.error) {
                    next[key] = event.error.message;
                } else {
                    delete next[key];
                }
                return next;
            });
        },
        [],
    );

    const handleSubmit = useCallback(async () => {
        if (!stripe || !elements) {
            return;
        }

        const trimmedName = cardholderName.trim();
        if (!trimmedName) {
            setFieldErrors((previous) => ({
                ...previous,
                cardholderName: t("features.payment-methods.cardholderNameRequired"),
            }));
            return;
        }

        const cardNumberElement = elements.getElement(CardNumberElement);
        if (!cardNumberElement) {
            return;
        }

        setIsSaving(true);

        try {
            const { error } = await stripe.confirmCardSetup(clientSecret, {
                payment_method: {
                    card: cardNumberElement,
                    billing_details: { name: trimmedName },
                },
            });

            if (error) {
                toast.error(t("features.payment-methods.saveFailed"), {
                    description: error.message,
                });
                return;
            }

            toast.success(t("features.payment-methods.saved"));
            onSuccess?.();
        } finally {
            setIsSaving(false);
        }
    }, [stripe, elements, cardholderName, clientSecret, t, onSuccess]);

    return (
        <div data-slot="card-form" className="flex flex-col gap-4">
            <div className="flex flex-col">
                <label
                    htmlFor="cardholder-name"
                    className="text-xs font-medium text-muted-foreground mb-1.5 block"
                >
                    {t("features.payment-methods.cardholderName")}
                </label>
                <Input
                    id="cardholder-name"
                    type="text"
                    value={cardholderName}
                    onChange={(event) => {
                        setCardholderName(event.target.value);
                        if (fieldErrors.cardholderName) {
                            setFieldErrors((previous) => {
                                const next = { ...previous };
                                delete next.cardholderName;
                                return next;
                            });
                        }
                    }}
                    disabled={isSaving}
                    autoComplete="cc-name"
                    aria-invalid={Boolean(fieldErrors.cardholderName)}
                    className={cn(
                        "rounded-2xl",
                        fieldErrors.cardholderName && "border-destructive",
                    )}
                />
                {fieldErrors.cardholderName && (
                    <p className="text-xs text-destructive mt-1.5">{fieldErrors.cardholderName}</p>
                )}
            </div>

            <StripeFieldWrapper
                label={t("features.payment-methods.cardNumber")}
                error={fieldErrors.cardNumber}
            >
                <CardNumberElement
                    options={elementOptions}
                    onChange={handleElementChange("cardNumber")}
                />
            </StripeFieldWrapper>

            <div className="grid grid-cols-2 gap-3">
                <StripeFieldWrapper
                    label={t("features.payment-methods.expiryDate")}
                    error={fieldErrors.expiry}
                >
                    <CardExpiryElement
                        options={elementOptions}
                        onChange={handleElementChange("expiry")}
                    />
                </StripeFieldWrapper>
                <StripeFieldWrapper
                    label={t("features.payment-methods.cvc")}
                    error={fieldErrors.cvc}
                >
                    <CardCvcElement
                        options={elementOptions}
                        onChange={handleElementChange("cvc")}
                    />
                </StripeFieldWrapper>
            </div>

            <div className="flex justify-end gap-2 mt-2">
                <Button type="button" variant="outline" onClick={onCancel} disabled={isSaving}>
                    {t("common.actions.cancel")}
                </Button>
                <Button type="button" onClick={handleSubmit} disabled={!isReady || isSaving}>
                    {isSaving ? t("common.actions.saving") : t("common.actions.save")}
                </Button>
            </div>
        </div>
    );
}

export function AddPaymentMethodDialog({
    open,
    onOpenChange,
    onSuccess,
}: AddPaymentMethodDialogProps) {
    const t = useTranslations();
    const [clientSecret, setClientSecret] = useState<string | null>(null);
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        if (!open) {
            queueMicrotask(() => setClientSecret(null));
            return;
        }

        let cancelled = false;
        queueMicrotask(() => setIsLoading(true));

        createPaymentMethodSetupIntent()
            .then((result) => {
                if (cancelled) {
                    return;
                }
                setClientSecret(result.clientSecret);
            })
            .catch((error: unknown) => {
                if (cancelled) {
                    return;
                }
                const message =
                    error instanceof Error
                        ? error.message
                        : t("features.payment-methods.setupIntentFailed");
                toast.error(t("features.payment-methods.setupIntentFailed"), {
                    description: message,
                });
                onOpenChange(false);
            })
            .finally(() => {
                if (cancelled) {
                    return;
                }
                setIsLoading(false);
            });

        return () => {
            cancelled = true;
        };
    }, [open, onOpenChange, t]);

    const elementsOptions = useMemo(() => {
        if (!clientSecret) {
            return null;
        }
        return {
            clientSecret,
            appearance: { theme: "stripe" as const },
        };
    }, [clientSecret]);

    const handleSuccess = useCallback(() => {
        onSuccess?.();
        onOpenChange(false);
    }, [onSuccess, onOpenChange]);

    const handleCancel = useCallback(() => {
        onOpenChange(false);
    }, [onOpenChange]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-slot="add-payment-method-dialog" className="max-w-md p-6">
                <DialogHeader>
                    <DialogTitle>{t("features.payment-methods.addTitle")}</DialogTitle>
                    <DialogDescription>
                        {t("features.payment-methods.addDescription")}
                    </DialogDescription>
                </DialogHeader>

                {isLoading && !clientSecret && (
                    <div className="flex items-center justify-center py-10">
                        <div className="size-6 rounded-full border-2 border-muted-foreground/30 border-t-primary animate-spin" />
                    </div>
                )}

                {clientSecret && elementsOptions && (
                    <Elements stripe={stripePromise} options={elementsOptions}>
                        <CardForm
                            onSuccess={handleSuccess}
                            onCancel={handleCancel}
                            clientSecret={clientSecret}
                        />
                    </Elements>
                )}
            </DialogContent>
        </Dialog>
    );
}

"use client";

import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import { cn } from "@workspace/ui/lib/utils";
import { formatAmount } from "@workspace/common";

type BookingFooterProps = {
    total: number;
    canSubmit: boolean;
    isSubmitting: boolean;
    onSubmit: () => void;
};

export function BookingFooter({ total, canSubmit, isSubmitting, onSubmit }: BookingFooterProps) {
    const t = useTranslations();

    return (
        <div className={cn("fixed inset-x-0 z-20 border-t bg-card px-4 py-3 bottom-0")}>
            <div className="container mx-auto flex h-full items-center justify-between gap-4">
                <p className="text-sm font-semibold text-slate-900">
                    {t("features.bookings.checkout.confirmFooterTotal", {
                        amount: formatAmount(total),
                    })}
                </p>
                <Button
                    onClick={onSubmit}
                    disabled={!canSubmit || isSubmitting}
                    variant="default"
                    size="xl"
                >
                    {isSubmitting ? "…" : t("features.bookings.checkout.confirmCta")}
                </Button>
            </div>
        </div>
    );
}

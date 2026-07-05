"use client";

import { useTranslations } from "next-intl";
import { ReceiptText } from "lucide-react";

import { SubscriptionInvoiceModel } from "@workspace/modules/subscriptions";
import { Button } from "@workspace/ui/components/button";

export function InvoiceList({ invoices }: { invoices: SubscriptionInvoiceModel[] }) {
    const t = useTranslations("features.subscriptions");

    if (invoices.length === 0) {
        return <p className="text-sm text-muted-foreground">{t("invoices.empty")}</p>;
    }

    return (
        <ul className="flex flex-col divide-y rounded-2xl border">
            {invoices.map((invoice) => (
                <li key={invoice.id} className="flex items-center justify-between gap-3 px-4 py-3">
                    <div className="flex items-center gap-3">
                        <ReceiptText className="size-5 text-muted-foreground" />
                        <div className="flex flex-col">
                            <span className="text-sm font-medium">
                                {invoice.number ?? invoice.id}
                            </span>
                            <span className="text-xs text-muted-foreground">{invoice.created}</span>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className="text-sm font-medium">
                            {invoice.amountPaid} {invoice.currency}
                        </span>
                        {invoice.hostedInvoiceUrl && (
                            <Button asChild variant="outline" size="sm" className="rounded-4xl">
                                <a
                                    href={invoice.hostedInvoiceUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {t("invoices.view")}
                                </a>
                            </Button>
                        )}
                    </div>
                </li>
            ))}
        </ul>
    );
}

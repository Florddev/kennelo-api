"use client";

import { useTranslations } from "next-intl";
import { ReceiptText } from "lucide-react";

import { EstablishmentPageHeader } from "@/features/establishments/components/establishment-page-header";

export default function EstablishmentInvoicesPage() {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-6">
            <EstablishmentPageHeader />
            <div className="flex flex-col items-center justify-center gap-3 py-16 text-center">
                <div className="flex items-center justify-center size-12 rounded-full bg-muted">
                    <ReceiptText className="size-6 text-muted-foreground" />
                </div>
                <p className="text-sm text-muted-foreground">
                    {t("features.establishments.manager.invoices.comingSoon")}
                </p>
            </div>
        </div>
    );
}

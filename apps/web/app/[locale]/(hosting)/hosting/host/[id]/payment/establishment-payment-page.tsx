"use client";

import { useTranslations } from "next-intl";
import { CreditCard } from "lucide-react";

export default function EstablishmentPaymentPage() {
    const t = useTranslations();

    return (
        <div className="flex flex-col items-center justify-center gap-3 py-16 text-center">
            <div className="flex items-center justify-center size-12 rounded-full bg-muted">
                <CreditCard className="size-6 text-muted-foreground" />
            </div>
            <p className="text-sm text-muted-foreground">
                {t("features.my-establishments.manager.payment.comingSoon")}
            </p>
        </div>
    );
}

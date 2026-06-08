"use client";

import { useTranslations } from "next-intl";
import { CreditCard } from "lucide-react";

import { ActivityPageHeader } from "@/features/activities/components/activity-page-header";

export default function ActivityPaymentPage() {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-6">
            <ActivityPageHeader />
            <div className="flex flex-col items-center justify-center gap-3 py-16 text-center">
                <div className="flex items-center justify-center size-12 rounded-full bg-muted">
                    <CreditCard className="size-6 text-muted-foreground" />
                </div>
                <p className="text-sm text-muted-foreground">
                    {t("features.activities.manager.payment.comingSoon")}
                </p>
            </div>
        </div>
    );
}

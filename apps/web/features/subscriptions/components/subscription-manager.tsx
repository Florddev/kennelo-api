"use client";

import { useTranslations } from "next-intl";

import { Skeleton } from "@workspace/ui/components/skeleton";

import { useSubscription } from "../hooks/use-subscription";
import { InvoiceList } from "./invoice-list";
import { PlanComparison } from "./plan-comparison";
import { SubscriptionStatusCard } from "./subscription-status-card";

export function SubscriptionManager() {
    const t = useTranslations("features.subscriptions");
    const { subscription, plans, invoices, isLoading, refresh } = useSubscription();

    if (isLoading) {
        return (
            <div className="flex flex-col gap-4">
                <Skeleton className="h-40 w-full rounded-2xl" />
                <Skeleton className="h-64 w-full rounded-2xl" />
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-8">
            {subscription && !subscription.isFree() && (
                <section className="flex flex-col gap-3">
                    <SubscriptionStatusCard subscription={subscription} onChanged={refresh} />
                </section>
            )}

            <section className="flex flex-col gap-3">
                <h2 className="text-lg font-semibold">{t("choosePlan")}</h2>
                <PlanComparison plans={plans} subscription={subscription} />
            </section>

            <section className="flex flex-col gap-3">
                <h2 className="text-lg font-semibold">{t("invoices.title")}</h2>
                <InvoiceList invoices={invoices} />
            </section>
        </div>
    );
}

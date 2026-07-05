"use client";

import { useTranslations } from "next-intl";

import { ActivityModel } from "@workspace/modules/activities";
import { Skeleton } from "@workspace/ui/components/skeleton";

import { useActivitySubscription } from "../hooks/use-activity-subscription";
import { InvoiceList } from "./invoice-list";
import { PlanComparison } from "./plan-comparison";
import { SubscriptionStatusCard } from "./subscription-status-card";

export function SubscriptionManager({ activity }: { activity: ActivityModel }) {
    const t = useTranslations("features.subscriptions");
    const { subscription, plans, invoices, isLoading, refresh } = useActivitySubscription(
        activity.id,
    );

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
                    <SubscriptionStatusCard
                        activityId={activity.id}
                        subscription={subscription}
                        onChanged={refresh}
                    />
                </section>
            )}

            <section className="flex flex-col gap-3">
                <h2 className="text-lg font-semibold">{t("choosePlan")}</h2>
                <PlanComparison
                    activityId={activity.id}
                    plans={plans}
                    subscription={subscription}
                />
            </section>

            <section className="flex flex-col gap-3">
                <h2 className="text-lg font-semibold">{t("invoices.title")}</h2>
                <InvoiceList invoices={invoices} />
            </section>
        </div>
    );
}

"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";

import {
    Plan,
    SubscriptionModel,
    SubscriptionPlanModel,
    startSubscriptionCheckout,
} from "@workspace/modules/subscriptions";
import { Badge } from "@workspace/ui/components/badge";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { cn } from "@workspace/ui/lib/utils";

import { useAsyncState } from "@/hooks/use-async-state";
import { PlanFeaturesList } from "./plan-features-list";

type PlanComparisonProps = {
    plans: SubscriptionPlanModel[];
    subscription: SubscriptionModel | null;
};

export function PlanComparison({ plans, subscription }: PlanComparisonProps) {
    const t = useTranslations("features.subscriptions");
    const { execute, isLoading } = useAsyncState();
    const [pendingPlan, setPendingPlan] = useState<Plan | null>(null);

    const currentPlan = subscription?.plan ?? "free";

    const subscribe = (plan: Plan) => {
        setPendingPlan(plan);

        execute(() => startSubscriptionCheckout(plan), {
            displayError: true,
            onSuccess: (url) => {
                window.location.href = url;
            },
            onFailure: () => setPendingPlan(null),
        });
    };

    return (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {plans.map((plan) => {
                const isCurrent = plan.slug === currentPlan;

                return (
                    <Card key={plan.id} className={cn(isCurrent && "ring-2 ring-primary")}>
                        <CardHeader>
                            <div className="flex items-center justify-between gap-2">
                                <CardTitle>{plan.name}</CardTitle>
                                {isCurrent && <Badge>{t("currentPlan")}</Badge>}
                            </div>
                            <div className="mt-1 text-2xl font-semibold">
                                {plan.isFree()
                                    ? t("price.free")
                                    : t("price.perMonth", { value: plan.priceMonthly })}
                            </div>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <PlanFeaturesList plan={plan} />
                            {!plan.isFree() && !isCurrent && (
                                <Button
                                    className="rounded-4xl"
                                    disabled={isLoading}
                                    onClick={() => subscribe(plan.slug)}
                                >
                                    {isLoading && pendingPlan === plan.slug
                                        ? t("subscribing")
                                        : t("subscribe")}
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}

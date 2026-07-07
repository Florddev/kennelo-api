"use client";

import { useTranslations } from "next-intl";

import { SubscriptionModel } from "@workspace/modules/subscriptions";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";

import { CancelSubscriptionDialog } from "./cancel-subscription-dialog";
import { SubscriptionStatusBadge } from "./subscription-status-badge";

type SubscriptionStatusCardProps = {
    subscription: SubscriptionModel;
    onChanged?: () => void;
};

export function SubscriptionStatusCard({ subscription, onChanged }: SubscriptionStatusCardProps) {
    const t = useTranslations("features.subscriptions");
    const planName = subscription.planDetails?.name ?? t(`plans.${subscription.plan}`);

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between gap-2">
                    <CardTitle>{t("currentSubscription")}</CardTitle>
                    <SubscriptionStatusBadge status={subscription.status} />
                </div>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
                <div className="flex flex-col gap-1">
                    <span className="text-sm text-muted-foreground">{t("plan")}</span>
                    <span className="text-lg font-semibold">{planName}</span>
                </div>

                {subscription.currentPeriodEnd && (
                    <div className="flex flex-col gap-1">
                        <span className="text-sm text-muted-foreground">
                            {subscription.isCanceling() ? t("endsOn") : t("renewsOn")}
                        </span>
                        <span className="text-sm font-medium">{subscription.currentPeriodEnd}</span>
                    </div>
                )}

                {subscription.isCanceling() && (
                    <p className="text-sm text-muted-foreground">{t("cancelPending")}</p>
                )}

                {subscription.isEffective &&
                    !subscription.isFree() &&
                    !subscription.isCanceling() && (
                        <CancelSubscriptionDialog onSuccess={onChanged} />
                    )}
            </CardContent>
        </Card>
    );
}

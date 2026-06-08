"use client";

import { useTranslations } from "next-intl";
import { CheckCircle2, AlertCircle, CreditCard } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { Badge } from "@workspace/ui/components/badge";
import { createStripeOnboardingLink, ActivityModel } from "@workspace/modules/activities";

import { useAsyncState } from "@/hooks/use-async-state";

type StripeConnectCardProps = {
    activity: ActivityModel;
};

export function StripeConnectCard({ activity }: StripeConnectCardProps) {
    const t = useTranslations();
    const { execute, isLoading } = useAsyncState();

    const isConnected = activity.stripeChargesEnabled;
    const isStarted = Boolean(activity.stripeAccountId);
    const needsAction = isStarted && !isConnected;

    let title: string;
    let description: string;
    let buttonLabel: string;

    if (isConnected) {
        title = t("features.activities.detail.stripe.connectedTitle");
        description = t("features.activities.detail.stripe.connectedDescription");
        buttonLabel = "";
    } else if (needsAction) {
        title = t("features.activities.detail.stripe.pendingTitle");
        description = t("features.activities.detail.stripe.pendingDescription");
        buttonLabel = t("features.activities.detail.stripe.continueOnboarding");
    } else {
        title = t("features.activities.detail.stripe.notConnectedTitle");
        description = t("features.activities.detail.stripe.notConnectedDescription");
        buttonLabel = t("features.activities.detail.stripe.startOnboarding");
    }

    const handleConnect = async () => {
        const url = await execute(() => createStripeOnboardingLink(activity.id), {
            displayError: true,
        });
        if (url) {
            window.location.href = url;
        }
    };

    return (
        <Card data-slot="stripe-connect-card">
            <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-sm font-medium text-muted-foreground uppercase tracking-wider">
                    <CreditCard className="size-4" />
                    {t("features.activities.detail.sections.payments")}
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="flex items-start gap-3">
                    {isConnected ? (
                        <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-emerald-600" />
                    ) : (
                        <AlertCircle className="mt-0.5 size-5 shrink-0 text-amber-500" />
                    )}
                    <div className="flex-1">
                        <p className="text-sm font-medium">{title}</p>
                        <p className="text-sm text-muted-foreground mt-1">{description}</p>
                    </div>
                </div>

                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2 flex-wrap">
                        <Badge variant={isConnected ? "default" : "secondary"}>
                            {isConnected
                                ? t("features.activities.detail.stripe.chargesOn")
                                : t("features.activities.detail.stripe.chargesOff")}
                        </Badge>
                        <Badge variant={activity.stripePayoutsEnabled ? "default" : "secondary"}>
                            {activity.stripePayoutsEnabled
                                ? t("features.activities.detail.stripe.payoutsOn")
                                : t("features.activities.detail.stripe.payoutsOff")}
                        </Badge>
                    </div>
                    {!isConnected && (
                        <Button
                            onClick={handleConnect}
                            disabled={isLoading}
                            className="rounded-4xl"
                        >
                            {isLoading ? t("common.actions.loading") : buttonLabel}
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

"use client";

import { useTranslations } from "next-intl";
import { CheckCircle2, AlertCircle, CreditCard } from "lucide-react";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { Badge } from "@workspace/ui/components/badge";
import { createStripeOnboardingLink, EstablishmentModel } from "@workspace/modules/establishments";

import { useAsyncState } from "@/hooks/use-async-state";

type StripeConnectCardProps = {
    establishment: EstablishmentModel;
};

export function StripeConnectCard({ establishment }: StripeConnectCardProps) {
    const t = useTranslations();
    const { execute, isLoading } = useAsyncState();

    const isConnected = establishment.stripeChargesEnabled;
    const isStarted = Boolean(establishment.stripeAccountId);
    const needsAction = isStarted && !isConnected;

    let title: string;
    let description: string;
    let buttonLabel: string;

    if (isConnected) {
        title = t("features.establishments.detail.stripe.connectedTitle");
        description = t("features.establishments.detail.stripe.connectedDescription");
        buttonLabel = "";
    } else if (needsAction) {
        title = t("features.establishments.detail.stripe.pendingTitle");
        description = t("features.establishments.detail.stripe.pendingDescription");
        buttonLabel = t("features.establishments.detail.stripe.continueOnboarding");
    } else {
        title = t("features.establishments.detail.stripe.notConnectedTitle");
        description = t("features.establishments.detail.stripe.notConnectedDescription");
        buttonLabel = t("features.establishments.detail.stripe.startOnboarding");
    }

    const handleConnect = async () => {
        const url = await execute(() => createStripeOnboardingLink(establishment.id), {
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
                    {t("features.establishments.detail.sections.payments")}
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
                                ? t("features.establishments.detail.stripe.chargesOn")
                                : t("features.establishments.detail.stripe.chargesOff")}
                        </Badge>
                        <Badge
                            variant={establishment.stripePayoutsEnabled ? "default" : "secondary"}
                        >
                            {establishment.stripePayoutsEnabled
                                ? t("features.establishments.detail.stripe.payoutsOn")
                                : t("features.establishments.detail.stripe.payoutsOff")}
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

"use client";

import { useEffect, useMemo, useState } from "react";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import { CheckCircle2 } from "lucide-react";
import { ConnectAccountOnboarding, ConnectComponentsProvider } from "@stripe/react-connect-js";
import type { StripeConnectInstance } from "@stripe/connect-js";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { Badge } from "@workspace/ui/components/badge";
import { createStripeAccountSession, getMyStripeStatus } from "@workspace/modules/users";

import { initStripeConnect } from "@/lib/stripe-connect";
import { useAuth } from "@/features/auth";

export function EmbeddedConnectOnboarding() {
    const t = useTranslations();
    const { user, refreshUser } = useAuth();
    const [connectInstance, setConnectInstance] = useState<StripeConnectInstance | null>(null);
    const [error, setError] = useState<string | null>(null);

    const isConnected = Boolean(user?.stripeChargesEnabled);
    const hasAccount = Boolean(user?.stripeAccountId);

    useEffect(() => {
        if (!user || !hasAccount || isConnected) return;
        queueMicrotask(() => {
            getMyStripeStatus()
                .then((status) => {
                    if (status.chargesEnabled) refreshUser();
                })
                .catch(() => undefined);
        });
    }, [user, hasAccount, isConnected, refreshUser]);

    const fetchClientSecret = useMemo(
        () => async () => {
            const { clientSecret } = await createStripeAccountSession();
            return clientSecret;
        },
        [],
    );

    useEffect(() => {
        if (isConnected) return;
        queueMicrotask(() => {
            try {
                const instance = initStripeConnect(fetchClientSecret);
                setConnectInstance(instance);
            } catch (err) {
                const message = err instanceof Error ? err.message : "Unknown error";
                setError(message);
                toast.error(t("features.establishments.detail.stripe.embedded.sessionFailed"));
            }
        });
    }, [fetchClientSecret, isConnected, t]);

    let body: React.ReactNode;
    if (isConnected) {
        body = (
            <div className="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-emerald-600" />
                <div className="flex flex-col gap-1">
                    <p className="text-sm font-semibold text-emerald-900">
                        {t("features.establishments.detail.stripe.connectedTitle")}
                    </p>
                    <p className="text-sm text-emerald-800">
                        {t("features.establishments.detail.stripe.connectedDescription")}
                    </p>
                </div>
            </div>
        );
    } else if (error) {
        body = <p className="text-sm text-destructive">{error}</p>;
    } else if (connectInstance) {
        body = (
            <ConnectComponentsProvider connectInstance={connectInstance}>
                <ConnectAccountOnboarding
                    onExit={async () => {
                        try {
                            await getMyStripeStatus();
                        } catch {
                            return refreshUser();
                        }
                        refreshUser();
                    }}
                />
            </ConnectComponentsProvider>
        );
    } else {
        body = <div className="h-40 animate-pulse rounded-2xl bg-muted" />;
    }

    return (
        <Card data-slot="embedded-connect-onboarding" className="rounded-2xl">
            <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-sm font-medium uppercase tracking-wider text-muted-foreground">
                    {t("features.establishments.detail.stripe.embedded.title")}
                    {isConnected && (
                        <Badge variant="default" className="ms-2">
                            {t("features.establishments.detail.stripe.chargesOn")}
                        </Badge>
                    )}
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {!isConnected && (
                    <p className="text-sm text-muted-foreground">
                        {t("features.establishments.detail.stripe.embedded.description")}
                    </p>
                )}
                {body}
            </CardContent>
        </Card>
    );
}

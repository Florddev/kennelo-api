"use client";

import { useEffect, useMemo, useState } from "react";
import { useTranslations } from "next-intl";
import { toast } from "sonner";
import { Building2, CheckCircle2, Pencil } from "lucide-react";
import { ConnectAccountOnboarding, ConnectComponentsProvider } from "@stripe/react-connect-js";
import type { StripeConnectInstance } from "@stripe/connect-js";
import { Button } from "@workspace/ui/components/button";
import { Card, CardContent, CardHeader, CardTitle } from "@workspace/ui/components/card";
import { Badge } from "@workspace/ui/components/badge";
import { createStripeAccountSession, getMyStripeStatus, UserModel } from "@workspace/modules/users";

import { initStripeConnect } from "@/lib/stripe-connect";
import { useAuth } from "@/features/auth";

export function EmbeddedConnectOnboarding() {
    const t = useTranslations();
    const { user, refreshUser, establishments } = useAuth();
    const [isEditing, setIsEditing] = useState(false);

    const isConnected = Boolean(user?.stripeChargesEnabled);
    const showSummary = isConnected && !isEditing && user;

    return (
        <Card data-slot="embedded-connect-onboarding" className="rounded-2xl">
            <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-sm font-medium uppercase tracking-wider text-muted-foreground">
                    {t("features.establishments.detail.stripe.embedded.title")}
                    {isConnected && !isEditing && (
                        <Badge variant="default" className="ms-2">
                            {t("features.establishments.detail.stripe.chargesOn")}
                        </Badge>
                    )}
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                {showSummary && (
                    <ConnectedSummary
                        user={user}
                        establishmentNames={establishments.map((e) => e.name)}
                        onUpdate={() => setIsEditing(true)}
                    />
                )}
                {!showSummary && (
                    <OnboardingSection
                        isConnected={isConnected}
                        isEditing={isEditing}
                        hasAccount={Boolean(user?.stripeAccountId)}
                        onConnected={() => {
                            refreshUser();
                            setIsEditing(false);
                        }}
                        onCancelEdit={() => setIsEditing(false)}
                    />
                )}
            </CardContent>
        </Card>
    );
}

type ConnectedSummaryProps = {
    user: UserModel;
    establishmentNames: string[];
    onUpdate: () => void;
};

function ConnectedSummary({ user, establishmentNames, onUpdate }: ConnectedSummaryProps) {
    const t = useTranslations();
    const accountId = user.stripeAccountId ?? "";
    const accountIdMasked =
        accountId.length > 10 ? `${accountId.slice(0, 8)}…${accountId.slice(-4)}` : accountId;
    const fullName = `${user.firstName ?? ""} ${user.lastName ?? ""}`.trim() || user.email;

    return (
        <div className="flex flex-col gap-4">
            <div className="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-emerald-600" />
                <div className="flex flex-1 flex-col gap-1">
                    <p className="text-sm font-semibold text-emerald-900">
                        {t("features.establishments.detail.stripe.connectedTitle")}
                    </p>
                    <p className="text-sm text-emerald-800">
                        {t("features.hosting.bankAccount.connectedAs", {
                            name: fullName,
                            email: user.email,
                        })}
                    </p>
                    <p className="text-xs text-emerald-700 font-mono">{accountIdMasked}</p>
                </div>
            </div>

            <EstablishmentsList establishmentNames={establishmentNames} />

            <div className="rounded-2xl border border-dashed bg-muted/30 p-3">
                <p className="text-xs text-muted-foreground">
                    {t("features.hosting.bankAccount.perEstablishmentNote")}
                </p>
            </div>

            <div className="flex justify-end">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={onUpdate}
                    className="gap-1.5 rounded-4xl"
                >
                    <Pencil className="size-3.5" />
                    {t("features.hosting.bankAccount.updateDetails")}
                </Button>
            </div>
        </div>
    );
}

function EstablishmentsList({ establishmentNames }: { establishmentNames: string[] }) {
    const t = useTranslations();

    return (
        <div className="flex flex-col gap-2">
            <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                {t("features.hosting.bankAccount.coveredEstablishments")}
            </p>
            {establishmentNames.length === 0 && (
                <p className="text-sm text-muted-foreground">
                    {t("features.hosting.bankAccount.noEstablishmentsCovered")}
                </p>
            )}
            {establishmentNames.length > 0 && (
                <ul className="flex flex-col gap-1.5">
                    {establishmentNames.map((name) => (
                        <li
                            key={name}
                            className="flex items-center gap-2 rounded-2xl border bg-background px-3 py-2 text-sm"
                        >
                            <Building2 className="size-4 text-muted-foreground" />
                            <span className="flex-1 truncate">{name}</span>
                            <Badge variant="secondary" className="text-[10px]">
                                {t("features.hosting.bankAccount.usesThisAccount")}
                            </Badge>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

type OnboardingSectionProps = {
    isConnected: boolean;
    isEditing: boolean;
    hasAccount: boolean;
    onConnected: () => void;
    onCancelEdit: () => void;
};

function OnboardingSection({
    isConnected,
    isEditing,
    hasAccount,
    onConnected,
    onCancelEdit,
}: OnboardingSectionProps) {
    const t = useTranslations();
    const { refreshUser } = useAuth();
    const [connectInstance, setConnectInstance] = useState<StripeConnectInstance | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!hasAccount || isConnected) return;
        queueMicrotask(() => {
            getMyStripeStatus()
                .then((status) => {
                    if (status.chargesEnabled) refreshUser();
                })
                .catch(() => undefined);
        });
    }, [hasAccount, isConnected, refreshUser]);

    const fetchClientSecret = useMemo(
        () => async () => {
            const { clientSecret } = await createStripeAccountSession();
            return clientSecret;
        },
        [],
    );

    useEffect(() => {
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
    }, [fetchClientSecret, t]);

    if (error) {
        return <p className="text-sm text-destructive">{error}</p>;
    }

    return (
        <div className="flex flex-col gap-3">
            {!isEditing && (
                <p className="text-sm text-muted-foreground">
                    {t("features.establishments.detail.stripe.embedded.description")}
                </p>
            )}
            {connectInstance ? (
                <ConnectComponentsProvider connectInstance={connectInstance}>
                    <ConnectAccountOnboarding
                        onExit={async () => {
                            try {
                                await getMyStripeStatus();
                            } catch {
                                onConnected();
                                return;
                            }
                            onConnected();
                        }}
                    />
                </ConnectComponentsProvider>
            ) : (
                <div className="h-40 animate-pulse rounded-2xl bg-muted" />
            )}
            {isEditing && (
                <div className="flex justify-end">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={onCancelEdit}
                        className="rounded-4xl"
                    >
                        {t("common.actions.cancel")}
                    </Button>
                </div>
            )}
        </div>
    );
}

"use client";

import { useState } from "react";
import { resendVerification } from "@workspace/modules/users";
import { Alert, AlertAction, AlertTitle, AlertDescription } from "@workspace/ui/components/alert";
import { Button } from "@workspace/ui/components/button";
import { useTranslations } from "next-intl";
import { useAsyncState } from "@/hooks/use-async-state";
import { useAuth } from "@/features/auth/hooks/use-auth";

export function HostUnverifiedAlert() {
    const { user, isLoaded } = useAuth();
    const t = useTranslations();
    const { isLoading, execute } = useAsyncState();
    const [resent, setResent] = useState(false);

    if (!isLoaded || !user || user.isEmailVerified()) {
        return null;
    }

    const handleResend = async () => {
        await execute(() => resendVerification(), {
            onSuccess: () => setResent(true),
        });
    };

    return (
        <div className="px-4 pt-4 md:px-6">
            <Alert variant="destructive" data-slot="host-unverified-alert">
                <AlertTitle>{t("features.auth.hostUnverified.title")}</AlertTitle>
                <AlertDescription>{t("features.auth.hostUnverified.description")}</AlertDescription>
                <AlertAction>
                    {resent ? (
                        <span className="text-xs text-muted-foreground">
                            {t("features.auth.verifyEmail.resent")}
                        </span>
                    ) : (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={isLoading}
                            onClick={handleResend}
                        >
                            {t("features.auth.verifyEmail.resend")}
                        </Button>
                    )}
                </AlertAction>
            </Alert>
        </div>
    );
}

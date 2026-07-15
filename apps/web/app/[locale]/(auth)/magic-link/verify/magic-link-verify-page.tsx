"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { verifyMagicLink } from "@workspace/modules/users";
import { TwoFactorChallengeForm, PasswordRenewalForm } from "@/features/auth";
import { FieldDescription, FieldGroup } from "@workspace/ui/components/field";
import { useTranslations } from "next-intl";
import { useNavigation } from "@/hooks/use-navigation";
import { useAuth } from "@/features/auth/hooks/use-auth";
import { safeRedirectPath } from "@/lib/safe-redirect";
import { logger } from "@/lib/logger";

type VerificationState =
    | { status: "verifying" }
    | { status: "error" }
    | { status: "success" }
    | { status: "two-factor"; token: string }
    | { status: "password-expired"; token: string };

export default function MagicLinkVerifyPage() {
    const { routes, router, params } = useNavigation<{
        id?: string;
        expires?: string;
        signature?: string;
        redirect_url?: string;
    }>();
    const t = useTranslations();
    const { refreshUser } = useAuth();
    const [state, setState] = useState<VerificationState>({ status: "verifying" });

    const { id, expires, signature } = params;

    const handleSuccess = (locale: string) => {
        const target = safeRedirectPath(params.redirect_url);
        router.push(target ?? routes.Home({ locale }));
    };

    useEffect(() => {
        if (!id || !expires || !signature) {
            setState({ status: "error" });
            return;
        }

        verifyMagicLink({ id, expires, signature })
            .then(async (result) => {
                if ("twoFactor" in result) {
                    setState({ status: "two-factor", token: result.challengeToken });
                    return;
                }

                if ("passwordExpired" in result) {
                    setState({ status: "password-expired", token: result.challengeToken });
                    return;
                }

                setState({ status: "success" });
                const freshUser = await refreshUser();
                handleSuccess(freshUser?.locale ?? "en");
            })
            .catch((error: unknown) => {
                logger.error("Magic link verification failed:", error);
                setState({ status: "error" });
            });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [id, expires, signature]);

    const magicLinkTitle = t("features.auth.magicLink.title");

    const copy = {
        verifying: {
            title: magicLinkTitle,
            description: t("features.auth.magicLink.verifying"),
        },
        success: {
            title: magicLinkTitle,
            description: t("features.auth.magicLink.success"),
        },
        error: {
            title: magicLinkTitle,
            description: t("features.auth.magicLink.error"),
        },
        "two-factor": {
            title: t("features.auth.twoFactor.challenge.title"),
            description: t("features.auth.twoFactor.challenge.description"),
        },
        "password-expired": {
            title: t("features.auth.passwordExpired.title"),
            description: t("features.auth.passwordExpired.description"),
        },
    } as const;

    const { title, description } = copy[state.status];

    return (
        <div className="flex sm:min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 w-full">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-6">
                    <FieldGroup className="gap-8">
                        <div className="flex flex-col items-center gap-3 text-center">
                            <h1 className="text-3xl font-bold">{title}</h1>
                            <p className="text-muted-foreground">{description}</p>
                        </div>
                        {state.status === "two-factor" && (
                            <TwoFactorChallengeForm
                                challengeToken={state.token}
                                onSuccess={handleSuccess}
                            />
                        )}
                        {state.status === "password-expired" && (
                            <PasswordRenewalForm
                                challengeToken={state.token}
                                onSuccess={handleSuccess}
                            />
                        )}
                    </FieldGroup>
                    <FieldDescription className="px-6 text-center">
                        <Link href={routes.Login()} className="text-primary hover:underline">
                            {t("features.auth.backToLogin")}
                        </Link>
                    </FieldDescription>
                </div>
            </div>
        </div>
    );
}

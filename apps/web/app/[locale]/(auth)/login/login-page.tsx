"use client";

import { useState } from "react";
import Link from "next/link";
import {
    LoginForm,
    GoogleSignInButton,
    TwoFactorChallengeForm,
    PasswordRenewalForm,
} from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { useTranslations } from "next-intl";
import { FieldDescription, FieldGroup } from "@workspace/ui/components/field";
import { safeRedirectPath } from "@/lib/safe-redirect";

type LoginChallenge =
    | { type: "two-factor"; token: string }
    | { type: "password-expired"; token: string }
    | null;

function renderChallengeForm(
    challenge: LoginChallenge,
    onSuccess: (locale: string) => void,
    onTwoFactorRequired: (token: string) => void,
    onPasswordExpired: (token: string) => void,
) {
    if (challenge?.type === "two-factor") {
        return <TwoFactorChallengeForm challengeToken={challenge.token} onSuccess={onSuccess} />;
    }

    if (challenge?.type === "password-expired") {
        return <PasswordRenewalForm challengeToken={challenge.token} onSuccess={onSuccess} />;
    }

    return (
        <div className="flex flex-col gap-6">
            <LoginForm
                onSuccess={onSuccess}
                onTwoFactorRequired={onTwoFactorRequired}
                onPasswordExpired={onPasswordExpired}
            />
            <GoogleSignInButton
                onSuccess={onSuccess}
                onTwoFactorRequired={onTwoFactorRequired}
                onPasswordExpired={onPasswordExpired}
            />
        </div>
    );
}

export default function LoginPage() {
    const { routes, router, params } = useNavigation<{ redirect_url?: string }>();
    const t = useTranslations();
    const [challenge, setChallenge] = useState<LoginChallenge>(null);

    const handleSuccess = (locale: string) => {
        const target = safeRedirectPath(params.redirect_url);
        router.push(target ?? routes.Home({ locale }));
    };

    const handleTwoFactorRequired = (token: string) => setChallenge({ type: "two-factor", token });
    const handlePasswordExpired = (token: string) =>
        setChallenge({ type: "password-expired", token });

    const copy = {
        login: {
            title: t("features.auth.login.title"),
            description: t("features.auth.login.description"),
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

    const { title, description } = copy[challenge?.type ?? "login"];

    return (
        <div className="flex sm:min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 w-full">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-6">
                    <FieldGroup className="gap-12">
                        <div className="flex flex-col items-center gap-2 text-center">
                            <h1 className="text-3xl font-bold">{title}</h1>
                            <p className="text-muted-foreground">{description}</p>
                        </div>
                        {renderChallengeForm(
                            challenge,
                            handleSuccess,
                            handleTwoFactorRequired,
                            handlePasswordExpired,
                        )}
                    </FieldGroup>
                    {!challenge && (
                        <FieldDescription className="px-6 text-center">
                            {t("features.auth.noAccount")}{" "}
                            <Link
                                href={routes.Register(
                                    params?.redirect_url
                                        ? { search_params: { redirect_url: params.redirect_url } }
                                        : {},
                                )}
                                className="text-primary hover:underline"
                            >
                                {t("features.auth.register.here")}
                            </Link>
                        </FieldDescription>
                    )}
                </div>
            </div>
        </div>
    );
}

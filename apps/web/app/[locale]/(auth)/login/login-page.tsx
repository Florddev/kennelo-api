"use client";

import { useState } from "react";
import Link from "next/link";
import { LoginForm, GoogleSignInButton, TwoFactorChallengeForm } from "@/features/auth";
import { useNavigation } from "@/hooks/use-navigation";
import { useTranslations } from "next-intl";
import { FieldDescription, FieldGroup } from "@workspace/ui/components/field";

export default function LoginPage() {
    const { routes, router, params } = useNavigation<{ redirect_url?: string }>();
    const t = useTranslations();
    const [challengeToken, setChallengeToken] = useState<string | null>(null);

    const handleSuccess = (locale: string) => {
        if (params.redirect_url) {
            router.push(params.redirect_url as string);
            return;
        }
        router.push(routes.Home({ locale }));
    };

    return (
        <div className="flex sm:min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 w-full">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-6">
                    <FieldGroup className="gap-12">
                        <div className="flex flex-col items-center gap-2 text-center">
                            <h1 className="text-3xl font-bold">
                                {challengeToken
                                    ? t("features.auth.twoFactor.challenge.title")
                                    : t("features.auth.login.title")}
                            </h1>
                            <p className="text-muted-foreground">
                                {challengeToken
                                    ? t("features.auth.twoFactor.challenge.description")
                                    : t("features.auth.login.description")}
                            </p>
                        </div>
                        {challengeToken ? (
                            <TwoFactorChallengeForm
                                challengeToken={challengeToken}
                                onSuccess={handleSuccess}
                            />
                        ) : (
                            <div className="flex flex-col gap-6">
                                <LoginForm
                                    onSuccess={handleSuccess}
                                    onTwoFactorRequired={setChallengeToken}
                                />
                                <GoogleSignInButton
                                    onSuccess={handleSuccess}
                                    onTwoFactorRequired={setChallengeToken}
                                />
                            </div>
                        )}
                    </FieldGroup>
                    {!challengeToken && (
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

"use client";

import { useGoogleLogin } from "@react-oauth/google";
import { useLocale, useTranslations } from "next-intl";
import { loginWithGoogle } from "@workspace/modules/users";
import { Button } from "@workspace/ui/components/button";
import { useAsyncState } from "@/hooks/use-async-state";
import { useAuth } from "@/features/auth/hooks/use-auth";
import { localeOrDefault, type Locale } from "@/dictionaries";

function GoogleIcon() {
    return (
        <svg viewBox="0 0 24 24" className="size-4" aria-hidden="true">
            <path
                fill="#4285F4"
                d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47a5.4 5.4 0 0 1-2.4 3.58v2.84h3.86c2.26-2.09 3.56-5.17 3.56-8.66z"
            />
            <path
                fill="#34A853"
                d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-2.84c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09A11.99 11.99 0 0 0 12 24z"
            />
            <path
                fill="#FBBC05"
                d="M5.27 14.45a7.2 7.2 0 0 1 0-4.59V6.77H1.29a12 12 0 0 0 0 10.77l3.98-3.09z"
            />
            <path
                fill="#EA4335"
                d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0A11.99 11.99 0 0 0 1.29 6.77l3.98 3.09C6.22 6.86 8.87 4.75 12 4.75z"
            />
        </svg>
    );
}

export function GoogleSignInButton({
    onSuccess,
    onTwoFactorRequired,
}: {
    onSuccess?: (locale: Locale) => void;
    onTwoFactorRequired?: (challengeToken: string) => void;
}) {
    const clientId = process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID;
    const locale = useLocale();
    const t = useTranslations();
    const { isLoading, execute } = useAsyncState();
    const { refreshUser } = useAuth();

    const login = useGoogleLogin({
        flow: "implicit",
        scope: "openid email profile",
        onSuccess: async (response) => {
            const result = await execute(() => loginWithGoogle(response.access_token, locale));

            if (!result) {
                return;
            }

            if ("twoFactor" in result) {
                onTwoFactorRequired?.(result.challengeToken);
                return;
            }

            const freshUser = await refreshUser();
            onSuccess?.(localeOrDefault(freshUser?.locale));
        },
    });

    if (!clientId) {
        return null;
    }

    return (
        <div className="flex flex-col gap-6" data-slot="google-sign-in">
            <div className="flex items-center gap-4">
                <span className="h-px flex-1 bg-border" />
                <span className="text-xs text-muted-foreground">{t("features.auth.or")}</span>
                <span className="h-px flex-1 bg-border" />
            </div>
            <Button
                type="button"
                variant="flat"
                size="lg"
                className="w-full font-medium text-md"
                disabled={isLoading}
                onClick={() => login()}
            >
                {isLoading ? (
                    <span className="size-4 rounded-full border-2 border-muted-foreground/30 border-t-primary animate-spin" />
                ) : (
                    <GoogleIcon />
                )}
                {t("features.auth.continueWithGoogle")}
            </Button>
        </div>
    );
}

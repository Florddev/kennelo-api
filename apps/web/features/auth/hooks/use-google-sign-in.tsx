"use client";

import { useCallback } from "react";

import { useGoogleLogin } from "@react-oauth/google";
import { useLocale } from "next-intl";
import posthog from "posthog-js";
import { SocialLogin } from "@capgo/capacitor-social-login";

import { loginWithGoogle, type UserModel } from "@workspace/modules/users";

import { useAsyncState } from "@/hooks/use-async-state";
import { useAuth } from "@/features/auth/hooks/use-auth";
import { isNative } from "@/lib/platform";

type GoogleSignInHandlers = {
    onSuccess?: (user: UserModel | null) => void;
    onTwoFactorRequired?: (challengeToken: string) => void;
    onPasswordExpired?: (challengeToken: string) => void;
};

let nativeInitialized = false;

function isUserCancelled(error: unknown): boolean {
    return (error as { code?: string })?.code === "USER_CANCELLED";
}

async function nativeGoogleAccessToken(): Promise<string | null> {
    if (!nativeInitialized) {
        await SocialLogin.initialize({
            google: {
                webClientId: process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID,
                iOSClientId: process.env.NEXT_PUBLIC_GOOGLE_IOS_CLIENT_ID,
                iOSServerClientId: process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID,
                mode: "online",
            },
        });
        nativeInitialized = true;
    }

    try {
        const { result } = await SocialLogin.login({
            provider: "google",
            options: { scopes: ["profile", "email"] },
        });

        if (result.responseType !== "online") {
            return null;
        }

        return result.accessToken?.token ?? null;
    } catch (error) {
        if (isUserCancelled(error)) {
            return null;
        }

        throw error;
    }
}

export function useGoogleSignIn({
    onSuccess,
    onTwoFactorRequired,
    onPasswordExpired,
}: GoogleSignInHandlers) {
    const locale = useLocale();
    const { isLoading, execute } = useAsyncState();
    const { refreshUser } = useAuth();

    const authenticate = useCallback(
        (getToken: () => Promise<string | null>) =>
            execute(
                async () => {
                    const token = await getToken();

                    if (!token) {
                        return;
                    }

                    const auth = await loginWithGoogle(token, locale);

                    if ("twoFactor" in auth) {
                        onTwoFactorRequired?.(auth.challengeToken);
                        return;
                    }

                    if ("passwordExpired" in auth) {
                        onPasswordExpired?.(auth.challengeToken);
                        return;
                    }

                    const freshUser = await refreshUser();
                    posthog.capture("user_logged_in", { method: "google" });
                    onSuccess?.(freshUser);
                },
                { displayError: true },
            ),
        [execute, locale, onSuccess, onTwoFactorRequired, onPasswordExpired, refreshUser],
    );

    const webLogin = useGoogleLogin({
        flow: "implicit",
        scope: "openid email profile",
        onSuccess: (response) => {
            void authenticate(async () => response.access_token);
        },
    });

    const signIn = useCallback(() => {
        if (isNative()) {
            void authenticate(nativeGoogleAccessToken);
            return;
        }

        webLogin();
    }, [authenticate, webLogin]);

    return { signIn, isLoading };
}

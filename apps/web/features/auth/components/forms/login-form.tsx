"use client";

import Link from "next/link";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { loginUserSchema, type LoginUserInput, loginUser } from "@workspace/modules/users";
import { Button } from "@workspace/ui/components/button";
import { Alert, AlertDescription } from "@workspace/ui/components/alert";
import { useTranslations } from "next-intl";
import posthog from "posthog-js";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";
import { useNavigation } from "@/hooks/use-navigation";
import { useAuth } from "@/features/auth/hooks/use-auth";
import { localeOrDefault, type Locale } from "@/dictionaries";
import { Letter, LockKeyholeMinimalistic } from "@solar-icons/react";

export function LoginForm({
    onSuccess,
    onTwoFactorRequired,
    onPasswordExpired,
}: {
    onSuccess?: (locale: Locale) => void;
    onTwoFactorRequired?: (challengeToken: string) => void;
    onPasswordExpired?: (challengeToken: string) => void;
}) {
    const { error, isLoading, execute } = useAsyncState();
    const { refreshUser } = useAuth();
    const { routes } = useNavigation();
    const t = useTranslations();

    const { handleSubmit, control, setError } = useForm<LoginUserInput>({
        resolver: zodResolver(loginUserSchema),
        defaultValues: {
            email: "",
            password: "",
        },
    });

    const onSubmit = async (data: LoginUserInput) => {
        const result = await execute(() => loginUser(data), {
            setFieldError: setError,
        });

        if (!result) {
            return;
        }

        if ("twoFactor" in result) {
            onTwoFactorRequired?.(result.challengeToken);
            return;
        }

        if ("passwordExpired" in result) {
            onPasswordExpired?.(result.challengeToken);
            return;
        }

        const freshUser = await refreshUser();
        posthog.capture("user_logged_in", { method: "email" });
        onSuccess?.(localeOrDefault(freshUser?.locale));
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            <InputController
                name="email"
                type="email"
                control={control}
                label={t("common.fields.email")}
                placeholder={t("common.placeholders.email")}
                isLoading={isLoading}
                autoComplete="email"
                Icon={Letter}
            />
            <InputController
                name="password"
                type="password"
                control={control}
                label={t("common.fields.password")}
                placeholder={t("common.placeholders.password")}
                isLoading={isLoading}
                autoComplete="current-password"
                Icon={LockKeyholeMinimalistic}
                labelAction={
                    <Link
                        href={routes.ForgotPassword()}
                        className="text-sm font-medium text-muted-foreground hover:underline"
                    >
                        {t("features.auth.forgotPassword.link")}
                    </Link>
                }
            />
            {error && (
                <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}
            <Button
                type="submit"
                size="lg"
                className="w-full font-medium text-md"
                disabled={isLoading}
            >
                {isLoading ? t("features.auth.login.loading") : t("features.auth.login.title")}
            </Button>
        </form>
    );
}

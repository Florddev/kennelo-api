"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { loginUserSchema, type LoginUserInput, loginUser } from "@workspace/modules/users";
import { Button } from "@workspace/ui/components/button";
import { Alert, AlertDescription } from "@workspace/ui/components/alert";
import { useTranslations } from "next-intl";
import { KEnvelope1, KLocked2 } from "@workspace/ui/icons";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";
import { useAuth } from "@/features/auth/hooks/use-auth";
import { localeOrDefault, type Locale } from "@/dictionaries";

export function LoginForm({ onSuccess }: { onSuccess?: (locale: Locale) => void }) {
    const { error, isLoading, execute } = useAsyncState();
    const { refreshUser } = useAuth();
    const t = useTranslations();

    const { handleSubmit, control, setError } = useForm<LoginUserInput>({
        resolver: zodResolver(loginUserSchema),
        defaultValues: {
            email: "",
            password: "",
        },
    });

    const onSubmit = async (data: LoginUserInput) => {
        await execute(() => loginUser(data), {
            setFieldError: setError,
            onSuccess: async () => {
                const freshUser = await refreshUser();
                onSuccess?.(localeOrDefault(freshUser?.locale));
            },
        });
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
                Icon={KEnvelope1}
            />
            <InputController
                name="password"
                type="password"
                control={control}
                label={t("common.fields.password")}
                placeholder={t("common.placeholders.password")}
                isLoading={isLoading}
                autoComplete="current-password"
                Icon={KLocked2}
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

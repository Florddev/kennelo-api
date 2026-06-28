"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import {
    resetPasswordSchema,
    type ResetPasswordInput,
    resetPassword,
} from "@workspace/modules/users";
import { Button } from "@workspace/ui/components/button";
import { Alert, AlertDescription } from "@workspace/ui/components/alert";
import { useTranslations } from "next-intl";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";
import { LockKeyholeMinimalistic } from "@solar-icons/react";

export function ResetPasswordForm({
    token,
    email,
    onSuccess,
}: {
    token: string;
    email: string;
    onSuccess?: () => void;
}) {
    const { error, isLoading, execute } = useAsyncState();
    const t = useTranslations();

    const { handleSubmit, control, setError } = useForm<ResetPasswordInput>({
        resolver: zodResolver(resetPasswordSchema),
        defaultValues: {
            token,
            email,
            password: "",
            passwordConfirmation: "",
        },
    });

    const onSubmit = async (data: ResetPasswordInput) => {
        await execute(() => resetPassword(data), {
            setFieldError: setError,
            onSuccess,
        });
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            <InputController
                name="password"
                type="password"
                control={control}
                label={t("common.fields.password")}
                placeholder={t("common.placeholders.password")}
                isLoading={isLoading}
                autoComplete="new-password"
                Icon={LockKeyholeMinimalistic}
                description={t("common.fields.passwordDescription")}
                showPasswordIndicator
            />
            <InputController
                name="passwordConfirmation"
                type="password"
                control={control}
                label={t("common.fields.passwordConfirmation")}
                placeholder={t("common.placeholders.passwordConfirmation")}
                isLoading={isLoading}
                autoComplete="new-password"
                Icon={LockKeyholeMinimalistic}
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
                {isLoading
                    ? t("features.auth.resetPassword.loading")
                    : t("features.auth.resetPassword.submit")}
            </Button>
        </form>
    );
}

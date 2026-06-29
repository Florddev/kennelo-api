"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import {
    forgotPasswordSchema,
    type ForgotPasswordInput,
    forgotPassword,
} from "@workspace/modules/users";
import { Button } from "@workspace/ui/components/button";
import { Alert, AlertDescription } from "@workspace/ui/components/alert";
import { useTranslations } from "next-intl";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";
import { Letter } from "@solar-icons/react";

export function ForgotPasswordForm({ onSuccess }: { onSuccess?: () => void }) {
    const { error, isLoading, execute } = useAsyncState();
    const t = useTranslations();

    const { handleSubmit, control, setError } = useForm<ForgotPasswordInput>({
        resolver: zodResolver(forgotPasswordSchema),
        defaultValues: {
            email: "",
        },
    });

    const onSubmit = async (data: ForgotPasswordInput) => {
        await execute(() => forgotPassword(data), {
            setFieldError: setError,
            onSuccess,
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
                Icon={Letter}
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
                    ? t("features.auth.forgotPassword.loading")
                    : t("features.auth.forgotPassword.submit")}
            </Button>
        </form>
    );
}

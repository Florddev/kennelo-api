"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import {
    renewPasswordSchema,
    type RenewPasswordInput,
    renewExpiredPassword,
    type UserModel,
} from "@workspace/modules/users";
import { Button } from "@workspace/ui/components/button";
import { useTranslations } from "next-intl";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";
import { useAuth } from "@/features/auth/hooks/use-auth";
import { LockKeyholeMinimalistic } from "@solar-icons/react";

export function PasswordRenewalForm({
    challengeToken,
    onSuccess,
}: {
    challengeToken: string;
    onSuccess?: (user: UserModel | null) => void;
}) {
    const { isLoading, execute } = useAsyncState();
    const { refreshUser } = useAuth();
    const t = useTranslations();

    const { handleSubmit, control, setError } = useForm<RenewPasswordInput>({
        resolver: zodResolver(renewPasswordSchema),
        defaultValues: { password: "", passwordConfirmation: "" },
    });

    const onSubmit = async (data: RenewPasswordInput) => {
        await execute(() => renewExpiredPassword(challengeToken, data), {
            setFieldError: setError,
            onSuccess: async () => {
                const freshUser = await refreshUser();
                onSuccess?.(freshUser);
            },
        });
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            <InputController
                name="password"
                type="password"
                control={control}
                label={t("common.fields.newPassword")}
                placeholder={t("common.placeholders.newPassword")}
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
            <Button
                type="submit"
                size="lg"
                className="w-full font-medium text-md"
                disabled={isLoading}
            >
                {isLoading
                    ? t("features.auth.passwordExpired.loading")
                    : t("features.auth.passwordExpired.submit")}
            </Button>
        </form>
    );
}

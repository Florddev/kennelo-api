"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import {
    changePasswordSchema,
    type ChangePasswordInput,
    changePassword,
} from "@workspace/modules/users";
import { Button } from "@workspace/ui/components/button";
import { useTranslations } from "next-intl";
import { KLocked2 } from "@workspace/ui/icons";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";

export function ChangePasswordForm() {
    const { isLoading, execute } = useAsyncState();
    const t = useTranslations();

    const { handleSubmit, control, setError, reset } = useForm<ChangePasswordInput>({
        resolver: zodResolver(changePasswordSchema),
        defaultValues: {
            currentPassword: "",
            password: "",
            passwordConfirmation: "",
        },
    });

    const onSubmit = async (data: ChangePasswordInput) => {
        await execute(() => changePassword(data), {
            setFieldError: setError,
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <InputController
                name="currentPassword"
                type="password"
                control={control}
                label={t("common.fields.password")}
                placeholder={t("common.placeholders.password")}
                isLoading={isLoading}
                autoComplete="current-password"
                Icon={KLocked2}
            />
            <InputController
                name="password"
                type="password"
                control={control}
                label={t("common.fields.newPassword")}
                placeholder={t("common.placeholders.newPassword")}
                isLoading={isLoading}
                autoComplete="new-password"
                Icon={KLocked2}
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
                Icon={KLocked2}
            />
            <Button type="submit" disabled={isLoading}>
                {t("common.actions.update")}
            </Button>
        </form>
    );
}

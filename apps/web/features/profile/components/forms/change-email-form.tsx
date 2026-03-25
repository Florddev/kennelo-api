"use client";

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { changeEmailSchema, type ChangeEmailInput, changeEmail } from "@workspace/modules/users";
import { Button } from "@workspace/ui/components/button";
import { useTranslations } from "next-intl";
import { KEnvelope1, KLocked2 } from "@workspace/ui/icons";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";
import { useAuth } from "@/features/auth";

export function ChangeEmailForm() {
    const { user, refreshUser } = useAuth();
    const { isLoading, execute } = useAsyncState();
    const t = useTranslations();

    const { handleSubmit, control, setError, reset } = useForm<ChangeEmailInput>({
        resolver: zodResolver(changeEmailSchema),
        defaultValues: {
            email: "",
            password: "",
        },
    });

    useEffect(() => {
        if (user) {
            reset({ email: user.email, password: "" });
        }
    }, [user, reset]);

    const onSubmit = async (data: ChangeEmailInput) => {
        await execute(() => changeEmail(data), {
            setFieldError: setError,
            onSuccess: () => {
                refreshUser();
                reset({ email: data.email, password: "" });
            },
            displayError: true,
        });
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
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
            <Button type="submit" disabled={isLoading}>
                {t("common.actions.save")}
            </Button>
        </form>
    );
}

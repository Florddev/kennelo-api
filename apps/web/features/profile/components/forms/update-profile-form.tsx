"use client";

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import {
    updateProfileSchema,
    type UpdateProfileInput,
    updateProfile,
} from "@workspace/modules/users";
import { Button } from "@workspace/ui/components/button";
import { useTranslations } from "next-intl";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";
import { useAuth } from "@/features/auth";
import { usePhoneCountryCode } from "@/hooks/use-phone-country-code";

export function UpdateProfileForm() {
    const { user, refreshUser } = useAuth();
    const { isLoading, execute } = useAsyncState();
    const phoneCountryCode = usePhoneCountryCode();
    const t = useTranslations();

    const { handleSubmit, control, setError, reset } = useForm<UpdateProfileInput>({
        resolver: zodResolver(updateProfileSchema),
        defaultValues: {
            firstName: "",
            lastName: "",
            phone: "",
        },
    });

    useEffect(() => {
        if (user) {
            reset({
                firstName: user.firstName,
                lastName: user.lastName,
                phone: user.phone ?? "",
            });
        }
    }, [user, reset]);

    const onSubmit = async (data: UpdateProfileInput) => {
        await execute(() => updateProfile(data), {
            setFieldError: setError,
            onSuccess: () => refreshUser(),
        });
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="flex w-full gap-2">
                <InputController
                    name="firstName"
                    type="text"
                    control={control}
                    label={t("common.fields.firstName")}
                    placeholder={t("common.placeholders.firstName")}
                    isLoading={isLoading}
                    autoComplete="given-name"
                />
                <InputController
                    name="lastName"
                    type="text"
                    control={control}
                    label={t("common.fields.lastName")}
                    placeholder={t("common.placeholders.lastName")}
                    isLoading={isLoading}
                    autoComplete="family-name"
                />
            </div>
            <InputController
                name="phone"
                type="phone"
                control={control}
                label={t("common.fields.phone")}
                isLoading={isLoading}
                defaultCountry={phoneCountryCode}
            />
            <Button type="submit" disabled={isLoading}>
                {t("common.actions.update")}
            </Button>
        </form>
    );
}

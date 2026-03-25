"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { registerUserSchema, type RegisterUserInput, registerUser } from "@workspace/modules/users";
import { Button } from "@workspace/ui/components/button";
import { useLocale, useTranslations } from "next-intl";
import { KEnvelope1, KLocked2 } from "@workspace/ui/icons";
import { useAsyncState } from "@/hooks/use-async-state";
import { InputController } from "@/components/forms/input-controller";

export function RegisterForm({ onSuccess }: { onSuccess?: () => void }) {
    const locale = useLocale();
    const { isLoading, execute } = useAsyncState();
    const t = useTranslations();

    const { handleSubmit, control, setError } = useForm<RegisterUserInput>({
        resolver: zodResolver(registerUserSchema),
        defaultValues: {
            firstName: "",
            lastName: "",
            email: "",
            // phone: "",
            password: "",
            passwordConfirmation: "",
            locale,
        },
    });

    const onSubmit = async (data: RegisterUserInput) => {
        await execute(() => registerUser(data), {
            setFieldError: setError,
            onSuccess,
        });
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
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
                name="email"
                type="email"
                control={control}
                label={t("common.fields.email")}
                placeholder={t("common.placeholders.email")}
                isLoading={isLoading}
                autoComplete="email"
                Icon={KEnvelope1}
            />

            {/* <Controller
                name="phone"
                control={control}
                render={({ field, fieldState }) => (
                    <Field data-invalid={fieldState.invalid}>
                        <FieldLabel htmlFor={field.name}>
                            {t("common.fields.phone")} ({t("common.fields.optional")})
                        </FieldLabel>
                        <Input
                            {...field}
                            id={field.name}
                            type="tel"
                            aria-invalid={fieldState.invalid}
                            placeholder={t("common.placeholders.phone")}
                            disabled={isLoading}
                            autoComplete="tel"
                        />
                        {fieldState.invalid && <FieldError errors={[fieldState.error]} />}
                    </Field>
                )}
            /> */}

            <InputController
                name="password"
                type="password"
                control={control}
                label={t("common.fields.password")}
                placeholder={t("common.placeholders.password")}
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
            <Button type="submit" className="w-full p-6 font-medium text-md" disabled={isLoading}>
                {isLoading
                    ? t("features.auth.register.loading")
                    : t("features.auth.create-account")}
            </Button>
        </form>
    );
}

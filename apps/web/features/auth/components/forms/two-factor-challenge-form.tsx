"use client";

import { Controller, useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import {
    twoFactorChallengeSchema,
    type TwoFactorChallengeInput,
    verifyTwoFactorChallenge,
} from "@workspace/modules/users";
import { Button } from "@workspace/ui/components/button";
import { Alert, AlertDescription } from "@workspace/ui/components/alert";
import { Field, FieldError, FieldLabel } from "@workspace/ui/components/field";
import { Input } from "@workspace/ui/components/input";
import { InputOTP, InputOTPGroup, InputOTPSlot } from "@workspace/ui/components/input-otp";
import { useTranslations } from "next-intl";
import { useState } from "react";
import { useAsyncState } from "@/hooks/use-async-state";
import { useAuth } from "@/features/auth/hooks/use-auth";
import { localeOrDefault, type Locale } from "@/dictionaries";

export function TwoFactorChallengeForm({
    challengeToken,
    onSuccess,
}: {
    challengeToken: string;
    onSuccess?: (locale: Locale) => void;
}) {
    const { error, isLoading, execute } = useAsyncState();
    const { refreshUser } = useAuth();
    const t = useTranslations();
    const [useRecovery, setUseRecovery] = useState(false);

    const { handleSubmit, control } = useForm<TwoFactorChallengeInput>({
        resolver: zodResolver(twoFactorChallengeSchema),
        defaultValues: { code: "", recoveryCode: "" },
    });

    const onSubmit = async (data: TwoFactorChallengeInput) => {
        await execute(() => verifyTwoFactorChallenge(challengeToken, data), {
            onSuccess: async () => {
                const freshUser = await refreshUser();
                onSuccess?.(localeOrDefault(freshUser?.locale));
            },
        });
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            {useRecovery ? (
                <Controller
                    name="recoveryCode"
                    control={control}
                    render={({ field, fieldState }) => (
                        <Field data-invalid={fieldState.invalid}>
                            <FieldLabel htmlFor="recoveryCode">
                                {t("features.auth.twoFactor.challenge.recoveryLabel")}
                            </FieldLabel>
                            <Input
                                {...field}
                                id="recoveryCode"
                                value={field.value ?? ""}
                                disabled={isLoading}
                                autoComplete="one-time-code"
                                placeholder={t(
                                    "features.auth.twoFactor.challenge.recoveryPlaceholder",
                                )}
                            />
                            {fieldState.invalid && <FieldError errors={[fieldState.error]} />}
                        </Field>
                    )}
                />
            ) : (
                <Controller
                    name="code"
                    control={control}
                    render={({ field, fieldState }) => (
                        <Field data-invalid={fieldState.invalid} className="items-center gap-2">
                            <FieldLabel>
                                {t("features.auth.twoFactor.challenge.codeLabel")}
                            </FieldLabel>
                            <InputOTP
                                maxLength={6}
                                value={field.value ?? ""}
                                onChange={field.onChange}
                                disabled={isLoading}
                            >
                                <InputOTPGroup>
                                    {[0, 1, 2, 3, 4, 5].map((index) => (
                                        <InputOTPSlot
                                            key={index}
                                            index={index}
                                            className="size-11"
                                        />
                                    ))}
                                </InputOTPGroup>
                            </InputOTP>
                            {fieldState.invalid && <FieldError errors={[fieldState.error]} />}
                        </Field>
                    )}
                />
            )}

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
                    ? t("features.auth.twoFactor.challenge.loading")
                    : t("features.auth.twoFactor.challenge.submit")}
            </Button>

            <button
                type="button"
                onClick={() => setUseRecovery((value) => !value)}
                className="w-full text-center text-sm text-primary hover:underline"
            >
                {useRecovery
                    ? t("features.auth.twoFactor.challenge.useCode")
                    : t("features.auth.twoFactor.challenge.useRecovery")}
            </button>
        </form>
    );
}

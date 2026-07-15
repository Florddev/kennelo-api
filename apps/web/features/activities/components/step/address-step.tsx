"use client";

import { Control, Controller } from "react-hook-form";
import { useTranslations } from "next-intl";
import { Field, FieldLabel, FieldError } from "@workspace/ui/components/field";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@workspace/ui/components/select";
import { InputController } from "@/components/forms/input-controller";
import type { CreateActivityInput } from "@workspace/modules/activities";
import { StepShell } from "./step-shell";

const COUNTRY_CODES = ["FR", "BE", "DE", "IT", "ES", "NL", "LU", "CH"] as const;

type AddressStepProps = {
    control: Control<CreateActivityInput>;
    isLoading: boolean;
};

export function AddressStep({ control, isLoading }: AddressStepProps) {
    const t = useTranslations();

    return (
        <StepShell
            title={t("features.become-host.steps.address.title")}
            subtitle={t("features.become-host.steps.address.subtitle")}
        >
            <InputController
                name="address.line1"
                control={control}
                label={t("common.fields.addressLine1")}
                placeholder={t("common.placeholders.addressLine1")}
                isLoading={isLoading}
                type="text"
                compact
            />
            <InputController
                name="address.line2"
                control={control}
                label={t("common.fields.addressLine2")}
                placeholder={t("common.placeholders.addressLine2")}
                isLoading={isLoading}
                type="text"
                compact
            />
            <div className="grid grid-cols-2 gap-4">
                <InputController
                    name="address.city"
                    control={control}
                    label={t("common.fields.city")}
                    placeholder={t("common.placeholders.city")}
                    isLoading={isLoading}
                    type="text"
                    compact
                />
                <InputController
                    name="address.postalCode"
                    control={control}
                    label={t("common.fields.postalCode")}
                    placeholder={t("common.placeholders.postalCode")}
                    isLoading={isLoading}
                    type="text"
                    compact
                />
            </div>
            <div className="grid grid-cols-2 gap-4">
                <InputController
                    name="address.region"
                    control={control}
                    label={t("common.fields.region")}
                    placeholder={t("common.placeholders.region")}
                    isLoading={isLoading}
                    type="text"
                    compact
                />
                <Controller
                    name="address.country"
                    control={control}
                    render={({ field, fieldState }) => {
                        const showError =
                            fieldState.invalid && (fieldState.isTouched || fieldState.isDirty);

                        return (
                            <Field data-invalid={showError} className="gap-1.5 group">
                                <FieldLabel htmlFor="address-country">
                                    {t("common.fields.country")}
                                </FieldLabel>
                                <Select
                                    value={field.value}
                                    onValueChange={field.onChange}
                                    disabled={isLoading}
                                >
                                    <SelectTrigger
                                        id="address-country"
                                        size="sm"
                                        className="rounded-4xl w-full"
                                        onBlur={field.onBlur}
                                    >
                                        <SelectValue
                                            placeholder={t("common.placeholders.country")}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {COUNTRY_CODES.map((code) => (
                                            <SelectItem key={code} value={code}>
                                                {t(`common.countries.${code.toLowerCase()}`)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {showError && <FieldError errors={[fieldState.error]} />}
                            </Field>
                        );
                    }}
                />
            </div>
        </StepShell>
    );
}

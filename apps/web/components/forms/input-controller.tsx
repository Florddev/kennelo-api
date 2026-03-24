"use client";

import {
    Control,
    Controller,
    ControllerFieldState,
    ControllerRenderProps,
    FieldValues,
    Path,
} from "react-hook-form";
import { Field, FieldLabel, FieldError, FieldDescription } from "@workspace/ui/components/field";
import { InputGroup, InputGroupAddon, InputGroupInput } from "@workspace/ui/components/input-group";
import { PasswordStrengthIndicator } from "./password-strength-indicator";
import { PhoneInput } from "@workspace/ui/components/phone-input";
import { KIcon } from "@workspace/ui/components/icons";
import { useState } from "react";
import { useTranslations } from "next-intl";

type InputControllerProps<TFieldValues extends FieldValues> = {
    name: Path<TFieldValues>;
    control: Control<TFieldValues>;
    label?: string;
    description?: string;
    placeholder?: string;
    isLoading?: boolean;
    autoComplete?: string;
    showPasswordIndicator?: boolean;
    type?: string;
    Icon?: KIcon;
};

type InputFieldProps = Omit<InputControllerProps<FieldValues>, "name" | "control"> & {
    field: ControllerRenderProps<FieldValues, string>;
    fieldState: ControllerFieldState;
};

type TextInputSectionProps = {
    field: ControllerRenderProps<FieldValues, string>;
    showError: boolean;
    type?: string;
    placeholder?: string;
    isLoading?: boolean;
    autoComplete?: string;
    Icon?: KIcon;
    fieldId: string;
};

function TextInputSection({
    field,
    showError,
    type,
    placeholder,
    isLoading,
    autoComplete,
    Icon,
    fieldId,
}: TextInputSectionProps) {
    const [showPassword, setShowPassword] = useState(false);
    const t = useTranslations();

    const isPassword = type === "password";
    const inputType = isPassword && showPassword ? "text" : (type ?? "text");
    const toggleLabel = showPassword
        ? t("common.fields.passwordHide")
        : t("common.fields.passwordShow");

    return (
        <InputGroup className="bg-card py-6 px-0.5 rounded-2xl gap-1">
            <InputGroupInput
                {...field}
                id={fieldId}
                type={inputType}
                aria-invalid={showError}
                placeholder={placeholder}
                disabled={isLoading}
                autoComplete={autoComplete ?? "new-password"}
            />
            {Icon && (
                <InputGroupAddon>
                    <Icon className="size-5.5 text-muted-foreground group-data-[invalid=true]:text-destructive" />
                </InputGroupAddon>
            )}
            {isPassword && (
                <InputGroupAddon
                    align="inline-end"
                    className="text-sm cursor-pointer"
                    onClick={() => setShowPassword(!showPassword)}
                >
                    {toggleLabel}
                </InputGroupAddon>
            )}
        </InputGroup>
    );
}

function shouldShowError(fieldState: ControllerFieldState) {
    return fieldState.invalid && (fieldState.isTouched || fieldState.isDirty);
}

function InputField({
    field,
    fieldState,
    type,
    placeholder,
    isLoading,
    autoComplete,
    Icon,
    label,
    description,
    showPasswordIndicator,
}: InputFieldProps) {
    const isPassword = type === "password";
    const isPhone = type === "phone";
    const fieldId = field.name;
    const showError = shouldShowError(fieldState);

    return (
        <Field data-invalid={showError} className="gap-1.5 group">
            {label && <FieldLabel htmlFor={fieldId}>{label}</FieldLabel>}

            {isPhone ? (
                <PhoneInput
                    {...field}
                    id={fieldId}
                    aria-invalid={showError}
                    value={field.value || ""}
                    onChange={field.onChange}
                    disabled={isLoading}
                    autoComplete={autoComplete ?? "tel"}
                />
            ) : (
                <TextInputSection
                    field={field}
                    showError={showError}
                    type={type}
                    placeholder={placeholder}
                    isLoading={isLoading}
                    autoComplete={autoComplete}
                    Icon={Icon}
                    fieldId={fieldId}
                />
            )}

            {showError && <FieldError errors={[fieldState.error]} />}
            {description && <FieldDescription>{description}</FieldDescription>}
            {isPassword && showPasswordIndicator && (
                <PasswordStrengthIndicator value={field.value} />
            )}
        </Field>
    );
}

export function InputController<TFieldValues extends FieldValues>({
    name,
    control,
    ...rest
}: InputControllerProps<TFieldValues>) {
    return (
        <Controller
            name={name}
            control={control}
            render={({ field, fieldState }) => (
                <InputField
                    field={field as ControllerRenderProps<FieldValues, string>}
                    fieldState={fieldState}
                    {...rest}
                />
            )}
        />
    );
}

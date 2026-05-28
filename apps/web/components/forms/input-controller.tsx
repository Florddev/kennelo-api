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
import { useState } from "react";
import { useTranslations } from "next-intl";
import { Button } from "@workspace/ui/components/button";
import { Calendar } from "@workspace/ui/components/calendar";
import {
    Drawer,
    DrawerContent,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from "@workspace/ui/components/drawer";
import { Popover, PopoverContent, PopoverTrigger } from "@workspace/ui/components/popover";
import { Calendar as CalendarIcon } from "lucide-react";
import { IconProps } from "@solar-icons/react";

import { useIsMobile } from "@/hooks/use-mobile";

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
    Icon?: React.ComponentType<IconProps>;
    defaultCountry?: string;
};

type InputFieldProps = Omit<InputControllerProps<FieldValues>, "name" | "control"> & {
    field: ControllerRenderProps<FieldValues, string>;
    fieldState: ControllerFieldState;
    defaultCountry?: string;
};

type TextInputSectionProps = {
    field: ControllerRenderProps<FieldValues, string>;
    showError: boolean;
    type?: string;
    placeholder?: string;
    isLoading?: boolean;
    autoComplete?: string;
    Icon?: React.ComponentType<IconProps>;
    fieldId: string;
};

type NumberInputSectionProps = {
    field: ControllerRenderProps<FieldValues, string>;
    showError: boolean;
    placeholder?: string;
    isLoading?: boolean;
    fieldId: string;
};

type DateInputSectionProps = {
    field: ControllerRenderProps<FieldValues, string>;
    showError: boolean;
    placeholder?: string;
    isLoading?: boolean;
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
        <InputGroup className="bg-card py-5 md:py-6 px-0.5 rounded-2xl gap-1">
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

function NumberInputSection({
    field,
    showError,
    placeholder,
    isLoading,
    fieldId,
}: NumberInputSectionProps) {
    return (
        <InputGroup className="bg-card py-5 md:py-6 px-0.5 rounded-2xl gap-1">
            <InputGroupInput
                {...field}
                id={fieldId}
                type="number"
                step="0.1"
                min="0"
                aria-invalid={showError}
                placeholder={placeholder}
                disabled={isLoading}
                value={typeof field.value === "number" ? field.value : ""}
                onChange={(event) => {
                    const nextValue = event.target.value;
                    field.onChange(nextValue === "" ? null : Number(nextValue));
                }}
            />
        </InputGroup>
    );
}

function DateInputSection({
    field,
    showError,
    placeholder,
    isLoading,
    fieldId,
}: DateInputSectionProps) {
    const [open, setOpen] = useState(false);
    const isMobile = useIsMobile();
    const t = useTranslations();
    const selectedDate = field.value ? new Date(`${field.value}T00:00:00`) : undefined;

    const formattedDate = selectedDate
        ? new Intl.DateTimeFormat(undefined, {
              year: "numeric",
              month: "long",
              day: "numeric",
          }).format(selectedDate)
        : placeholder;

    const handleDateSelect = (date: Date | undefined) => {
        if (!date) {
            field.onChange("");
        } else {
            const formatted = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
            field.onChange(formatted);
        }
        setOpen(false);
    };

    const trigger = (
        <Button
            id={fieldId}
            type="button"
            variant="outline"
            disabled={isLoading}
            aria-invalid={showError}
            className="justify-between rounded-2xl bg-card w-full py-5 md:py-6"
        >
            {formattedDate}
            <CalendarIcon className="size-4 text-muted-foreground" />
        </Button>
    );

    const calendar = (
        <Calendar
            mode="single"
            captionLayout="dropdown"
            startMonth={new Date(1950, 0)}
            endMonth={new Date()}
            selected={selectedDate}
            onSelect={handleDateSelect}
            disabled={{ after: new Date() }}
        />
    );

    if (isMobile) {
        return (
            <Drawer open={open} onOpenChange={setOpen}>
                <DrawerTrigger asChild>{trigger}</DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>{t("common.fields.selectDate")}</DrawerTitle>
                    </DrawerHeader>
                    <div className="flex justify-center pb-4">{calendar}</div>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent className="w-fit p-0">{calendar}</PopoverContent>
        </Popover>
    );
}

function shouldShowError(fieldState: ControllerFieldState) {
    return fieldState.invalid && (fieldState.isTouched || fieldState.isDirty);
}

function InputControl({
    field,
    fieldState,
    type,
    placeholder,
    isLoading,
    autoComplete,
    Icon,
    defaultCountry,
}: Omit<InputFieldProps, "label" | "description" | "showPasswordIndicator">) {
    const fieldId = field.name;
    const showError = shouldShowError(fieldState);

    if (type === "phone") {
        return (
            <PhoneInput
                {...field}
                id={fieldId}
                aria-invalid={showError}
                value={field.value || ""}
                onChange={field.onChange}
                disabled={isLoading}
                autoComplete={autoComplete ?? "tel"}
                defaultCountry={defaultCountry as never}
            />
        );
    }

    if (type === "number") {
        return (
            <NumberInputSection
                field={field}
                showError={showError}
                placeholder={placeholder}
                isLoading={isLoading}
                fieldId={fieldId}
            />
        );
    }

    if (type === "date") {
        return (
            <DateInputSection
                field={field}
                showError={showError}
                placeholder={placeholder}
                isLoading={isLoading}
                fieldId={fieldId}
            />
        );
    }

    return (
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
    );
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
    defaultCountry,
}: InputFieldProps) {
    const isPassword = type === "password";
    const fieldId = field.name;
    const showError = shouldShowError(fieldState);

    return (
        <Field data-invalid={showError} className="gap-1.5 group">
            {label && <FieldLabel htmlFor={fieldId}>{label}</FieldLabel>}

            <InputControl
                field={field}
                fieldState={fieldState}
                type={type}
                placeholder={placeholder}
                isLoading={isLoading}
                autoComplete={autoComplete}
                Icon={Icon}
                defaultCountry={defaultCountry}
            />

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

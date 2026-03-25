"use client";

import { Control, Controller, FieldValues, Path } from "react-hook-form";
import { Field, FieldLabel, FieldError, FieldDescription } from "@workspace/ui/components/field";
import { Textarea } from "@workspace/ui/components/textarea";

type TextareaControllerProps<TFieldValues extends FieldValues> = {
    name: Path<TFieldValues>;
    control: Control<TFieldValues>;
    label?: string;
    description?: string;
    placeholder?: string;
    isLoading?: boolean;
    rows?: number;
};

export function TextareaController<TFieldValues extends FieldValues>({
    name,
    control,
    label,
    description,
    placeholder,
    isLoading,
    rows,
}: TextareaControllerProps<TFieldValues>) {
    return (
        <Controller
            name={name}
            control={control}
            render={({ field, fieldState }) => {
                const showError =
                    fieldState.invalid && (fieldState.isTouched || fieldState.isDirty);

                return (
                    <Field data-invalid={showError} className="gap-1.5 group">
                        {label && <FieldLabel htmlFor={field.name}>{label}</FieldLabel>}
                        <Textarea
                            {...field}
                            id={field.name}
                            aria-invalid={showError}
                            placeholder={placeholder}
                            disabled={isLoading}
                            rows={rows}
                            className="bg-card rounded-2xl"
                        />
                        {showError && <FieldError errors={[fieldState.error]} />}
                        {description && <FieldDescription>{description}</FieldDescription>}
                    </Field>
                );
            }}
        />
    );
}

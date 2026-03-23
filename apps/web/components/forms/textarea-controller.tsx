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
            render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid} className="gap-1.5 group">
                    {label && <FieldLabel htmlFor={field.name}>{label}</FieldLabel>}
                    <Textarea
                        {...field}
                        id={field.name}
                        aria-invalid={fieldState.invalid}
                        placeholder={placeholder}
                        disabled={isLoading}
                        rows={rows}
                        className="bg-card rounded-2xl"
                    />
                    {fieldState.invalid && <FieldError errors={[fieldState.error]} />}
                    {description && <FieldDescription>{description}</FieldDescription>}
                </Field>
            )}
        />
    );
}

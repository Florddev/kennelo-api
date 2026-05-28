"use client";

import React from "react";
import {
    type ControllerFieldState,
    type ControllerRenderProps,
    type FieldValues,
} from "react-hook-form";

import { cn } from "@workspace/ui/lib/utils";

import { type InlineFieldBaseProps } from "./types";
import { RowLabel, resolveInlineField } from "./shared";

export type InlineTextareaProps = InlineFieldBaseProps<string> & {
    field?: ControllerRenderProps<FieldValues, string>;
    fieldState?: ControllerFieldState;
};

export function InlineTextarea({
    field,
    fieldState,
    value,
    onChange,
    name,
    invalid,
    label,
    Icon,
    placeholder,
    isLoading,
    className,
}: InlineTextareaProps) {
    const resolved = resolveInlineField<string>({
        field,
        fieldState,
        value,
        onChange,
        name,
        invalid,
    });
    const textareaId = `inline-textarea-${resolved.name}`;

    return (
        <label
            htmlFor={textareaId}
            data-slot="inline-row"
            data-invalid={resolved.showError}
            className={cn(
                "p-3 md:p-4 border rounded-sm flex flex-col gap-2 transition-colors cursor-text",
                resolved.showError && "border-destructive",
                isLoading && "opacity-50 pointer-events-none",
                className,
            )}
        >
            <RowLabel Icon={Icon} label={label} />
            <textarea
                id={textareaId}
                placeholder={placeholder}
                disabled={isLoading}
                rows={3}
                name={resolved.name}
                value={resolved.value ?? ""}
                onBlur={resolved.onBlur}
                onChange={(event) => resolved.onChange(event.target.value)}
                ref={field?.ref as React.Ref<HTMLTextAreaElement> | undefined}
                className="bg-transparent outline-none text-sm placeholder:text-muted-foreground resize-none w-full"
            />
        </label>
    );
}

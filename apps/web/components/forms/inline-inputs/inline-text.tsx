"use client";

import React from "react";
import {
    type ControllerFieldState,
    type ControllerRenderProps,
    type FieldValues,
} from "react-hook-form";

import { cn } from "@workspace/ui/lib/utils";

import { type InlineFieldBaseProps } from "./types";
import { RowLabel, resolveInlineField, rowCn } from "./shared";

export type InlineTextProps = InlineFieldBaseProps<string> & {
    field?: ControllerRenderProps<FieldValues, string>;
    fieldState?: ControllerFieldState;
    inputType?: "text" | "email" | "url" | "tel";
};

export function InlineText({
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
    inputType = "text",
}: InlineTextProps) {
    const resolved = resolveInlineField<string>({
        field,
        fieldState,
        value,
        onChange,
        name,
        invalid,
    });
    const inputId = `inline-text-${resolved.name}`;

    return (
        <label
            htmlFor={inputId}
            data-slot="inline-row"
            data-invalid={resolved.showError}
            className={cn(rowCn(resolved.showError, isLoading, className), "cursor-text")}
        >
            <RowLabel Icon={Icon} label={label} />
            <input
                id={inputId}
                type={inputType}
                placeholder={placeholder}
                disabled={isLoading}
                name={resolved.name}
                value={resolved.value ?? ""}
                onBlur={resolved.onBlur}
                onChange={(event) => resolved.onChange(event.target.value)}
                ref={field?.ref as React.Ref<HTMLInputElement> | undefined}
                className="bg-transparent outline-none text-end ms-2 flex-1 min-w-0 text-sm placeholder:text-muted-foreground"
            />
        </label>
    );
}

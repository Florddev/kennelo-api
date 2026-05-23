"use client";

import React from "react";

import { Button } from "@workspace/ui/components/button";

import { type InlineChoiceFieldProps } from "./types";
import { RowLabel, resolveInlineField, rowCn } from "./shared";

export function InlineButtonList({
    field,
    fieldState,
    value,
    onChange,
    name,
    invalid,
    label,
    Icon,
    isLoading,
    options = [],
    className,
}: InlineChoiceFieldProps) {
    const resolved = resolveInlineField<string>({
        field,
        fieldState,
        value,
        onChange,
        name,
        invalid,
    });

    return (
        <div
            data-slot="inline-row"
            data-invalid={resolved.showError}
            className={rowCn(resolved.showError, isLoading, className)}
        >
            <RowLabel Icon={Icon} label={label} />
            <div className="flex gap-0.5 items-center">
                {options.map((option) => (
                    <Button
                        key={option.value}
                        type="button"
                        variant={resolved.value === option.value ? "default" : "flat"}
                        size="sm"
                        disabled={isLoading}
                        onClick={() =>
                            resolved.onChange(resolved.value === option.value ? "" : option.value)
                        }
                    >
                        {option.Icon && <option.Icon className="size-4" />}
                        {option.label}
                    </Button>
                ))}
            </div>
        </div>
    );
}

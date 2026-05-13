"use client";

import React from "react";
import {
    type ControllerFieldState,
    type ControllerRenderProps,
    type FieldValues,
} from "react-hook-form";

import { SwitchChecker } from "@workspace/ui/components/switch-checker";

import { type InlineFieldBaseProps } from "./types";
import { RowLabel, resolveInlineField, rowCn } from "./shared";

export type InlineBooleanProps = InlineFieldBaseProps & {
    field?: ControllerRenderProps<FieldValues, string>;
    fieldState?: ControllerFieldState;
};

export function InlineBoolean({
    field,
    fieldState,
    value,
    onChange,
    name,
    invalid,
    label,
    Icon,
    isLoading,
    className,
}: InlineBooleanProps) {
    const resolved = resolveInlineField<boolean>({
        field,
        fieldState,
        value,
        onChange,
        name,
        invalid,
    });
    const switchId = `inline-boolean-${resolved.name}`;

    return (
        <label
            htmlFor={switchId}
            data-slot="inline-row"
            data-invalid={resolved.showError}
            className={rowCn(resolved.showError, isLoading, className, true)}
        >
            <RowLabel Icon={Icon} label={label} />
            <SwitchChecker
                id={switchId}
                checked={Boolean(resolved.value)}
                onCheckedChange={resolved.onChange}
                size="sm"
                disabled={isLoading}
            />
        </label>
    );
}

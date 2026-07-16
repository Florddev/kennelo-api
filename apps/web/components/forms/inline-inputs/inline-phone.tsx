"use client";

import {
    type ControllerFieldState,
    type ControllerRenderProps,
    type FieldValues,
} from "react-hook-form";

import { PhoneInput } from "@workspace/ui/components/phone-input";
import { cn } from "@workspace/ui/lib/utils";

import { type InlineFieldBaseProps } from "./types";
import { RowLabel, resolveInlineField, rowCn } from "./shared";

export type InlinePhoneProps = InlineFieldBaseProps<string> & {
    field?: ControllerRenderProps<FieldValues, string>;
    fieldState?: ControllerFieldState;
    defaultCountry?: string;
};

const PHONE_RESET_CLASS = cn(
    "flex-1 min-w-0 justify-end",
    "[&_input]:h-auto [&_input]:flex-1 [&_input]:min-w-0 [&_input]:rounded-none [&_input]:border-0",
    "[&_input]:bg-transparent [&_input]:py-0 [&_input]:text-end [&_input]:text-sm [&_input]:shadow-none",
    "[&_input]:focus-visible:ring-0 [&_input]:focus-visible:border-0",
    "[&>button]:h-auto [&>button]:rounded-4xl [&>button]:border [&>button]:bg-transparent",
    "[&>button]:py-1 [&>button]:px-2 [&>button]:shadow-none",
);

export function InlinePhone({
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
    defaultCountry,
}: InlinePhoneProps) {
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
            <PhoneInput
                name={resolved.name}
                value={resolved.value ?? ""}
                onChange={(next) => resolved.onChange(next ?? "")}
                onBlur={resolved.onBlur}
                disabled={isLoading}
                placeholder={placeholder}
                defaultCountry={defaultCountry as never}
                className={PHONE_RESET_CLASS}
            />
        </div>
    );
}

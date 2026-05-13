import React from "react";
import { type IconProps } from "@solar-icons/react";
import { type ControllerFieldState } from "react-hook-form";

import { cn } from "@workspace/ui/lib/utils";

import { type InlineFieldBridgeProps, type InlineValue } from "./types";

export function rowCn(
    showError: boolean,
    isLoading?: boolean,
    extra?: string,
    clickable?: boolean,
) {
    return cn(
        "h-12 p-3 md:gap-4 md:h-16 md:p-4 border rounded-sm flex justify-between items-center transition-colors",
        showError && "border border-destructive bg-destructive/5 text-destructive",
        isLoading && "pointer-events-none opacity-50",
        clickable && "cursor-pointer",
        extra,
    );
}

export function RowLabel({
    Icon,
    label,
    className,
}: {
    Icon?: React.ComponentType<IconProps>;
    label: string;
    className?: string;
}) {
    return (
        <div
            className={cn(
                "flex gap-2.5 items-center text-sm md:text-base font-semibold shrink-0",
                className,
            )}
        >
            {Icon && <Icon className="size-5 md:size-6" />}
            <span>{label}</span>
        </div>
    );
}

export function shouldShowError(
    fieldState?: Pick<ControllerFieldState, "invalid" | "isTouched" | "isDirty">,
    invalid?: boolean,
) {
    if (invalid !== undefined) return invalid;
    if (!fieldState) return false;
    return fieldState.invalid && (fieldState.isTouched || fieldState.isDirty);
}

export function resolveInlineField<TValue extends InlineValue>({
    field,
    fieldState,
    value,
    onChange,
    name,
    invalid,
}: InlineFieldBridgeProps<TValue>) {
    const resolvedName = field?.name ?? name ?? "inline-field";
    const resolvedValue = (field?.value ?? value) as TValue;
    const resolvedOnChange =
        onChange ??
        ((nextValue: TValue) => {
            field?.onChange(nextValue);
        });

    return {
        name: resolvedName,
        value: resolvedValue,
        onChange: resolvedOnChange,
        onBlur: field?.onBlur ?? (() => undefined),
        showError: shouldShowError(fieldState, invalid),
    };
}

export function ageToIso(years: number, months: number): string {
    const now = new Date();
    const d = new Date(now.getFullYear() - years, now.getMonth() - months, now.getDate());
    return [
        String(d.getFullYear()).padStart(4, "0"),
        String(d.getMonth() + 1).padStart(2, "0"),
        String(d.getDate()).padStart(2, "0"),
    ].join("-");
}

export function isoToAge(iso: string): { years: number; months: number } {
    const birth = new Date(`${iso}T00:00:00`);
    const now = new Date();
    let y = now.getFullYear() - birth.getFullYear();
    let m = now.getMonth() - birth.getMonth();
    if (m < 0) {
        y--;
        m += 12;
    }
    return { years: Math.max(0, y), months: Math.max(0, m) };
}

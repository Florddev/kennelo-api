"use client";

import React from "react";

import { Badge } from "@workspace/ui/components/badge";
import { cn } from "@workspace/ui/lib/utils";

import { type InlineChoiceFieldProps, type InlineOption } from "./types";
import { RowLabel, resolveInlineField } from "./shared";

export type BadgeListPickerProps = {
    value: string;
    onChange: (value: string) => void;
    options: InlineOption[];
};

export function BadgeListPicker({
    value,
    onChange,
    options,
}: BadgeListPickerProps): React.ReactElement {
    return (
        <div className="flex flex-wrap gap-x-1 gap-y-2">
            {options.map((option) => {
                const isSelected = option.value === value;
                return (
                    <Badge
                        key={option.value}
                        asChild
                        variant={isSelected ? "default" : "flat"}
                        className={cn(
                            "p-4 md:p-5 rounded-full cursor-pointer text-sm font-medium",
                            // "h-8 px-3 md:h-10 md:px-6 rounded-full bg-card cursor-pointer text-sm font-medium",
                            // isSelected && "ring-[1px] bg-primary/5 border-primary ring-primary text-primary",
                        )}
                    >
                        <button
                            type="button"
                            onClick={() => onChange(isSelected ? "" : option.value)}
                        >
                            {option.label}
                        </button>
                    </Badge>
                );
            })}
        </div>
    );
}

export function InlineBadgeList({
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
            className={cn(
                "p-3 md:p-4 border rounded-sm flex flex-col gap-2 transition-colors",
                resolved.showError && "border-destructive",
                isLoading && "opacity-50 pointer-events-none",
                className,
            )}
        >
            <RowLabel Icon={Icon} label={label} />
            <BadgeListPicker
                value={resolved.value ?? ""}
                onChange={resolved.onChange}
                options={options}
            />
        </div>
    );
}

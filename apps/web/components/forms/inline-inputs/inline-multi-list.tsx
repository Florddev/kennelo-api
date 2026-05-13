"use client";

import React, { useRef } from "react";
import {
    type Option as SelectorOption,
    MultipleSelector,
} from "@workspace/ui/components/multi-select";
import { cn } from "@workspace/ui/lib/utils";

import { type InlineMultiChoiceFieldProps } from "./types";
import { RowLabel, resolveInlineField } from "./shared";
import { useTranslations } from "next-intl";

export function InlineMultiList({
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
    options = [],
    creatable,
    className,
}: InlineMultiChoiceFieldProps) {
    const t = useTranslations();
    const containerRef = useRef<HTMLDivElement>(null);
    const resolved = resolveInlineField<string[]>({
        field,
        fieldState,
        value,
        onChange,
        name,
        invalid,
    });

    const selectorOptions: SelectorOption[] = options.map((option) => ({
        value: option.value,
        label: option.label,
    }));

    const selectedValues: string[] = Array.isArray(resolved.value)
        ? (resolved.value as string[])
        : [];
    const selectedOptions: SelectorOption[] = selectedValues.map(
        (value) =>
            selectorOptions.find((option) => option.value === value) ?? { value, label: value },
    );

    return (
        <div
            ref={containerRef}
            data-slot="inline-row"
            data-invalid={resolved.showError}
            className={cn(
                "p-3 md:p-4 border rounded-sm flex flex-col gap-2 transition-colors",
                resolved.showError && "border-destructive",
                isLoading && "opacity-50 pointer-events-none",
                className,
            )}
            onClick={() =>
                (containerRef.current?.querySelector("input") as HTMLInputElement | null)?.focus()
            }
        >
            <RowLabel Icon={Icon} label={label} />
            <MultipleSelector
                value={selectedOptions}
                options={selectorOptions}
                onChange={(values) => resolved.onChange(values.map((option) => option.value))}
                placeholder={placeholder}
                disabled={isLoading}
                creatable={creatable}
                openOnFocus={selectorOptions.length > 0}
                hidePlaceholderWhenSelected
                emptyIndicator={
                    <p className="text-center text-sm text-muted-foreground">
                        {t("common.fields.noResults")}
                    </p>
                }
                className="p-0 min-h-fit border-none focus-within:ring-0"
                hideClearAllButton
            />
        </div>
    );
}

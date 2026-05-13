"use client";

import React, { useRef, useState } from "react";
import {
    type ControllerFieldState,
    type ControllerRenderProps,
    type FieldValues,
} from "react-hook-form";
import { Minus, Plus } from "lucide-react";
import { useTranslations } from "next-intl";

import { Button } from "@workspace/ui/components/button";
import {
    Drawer,
    DrawerContent,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from "@workspace/ui/components/drawer";
import { NumberScrollPicker } from "@workspace/ui/components/number-scroll-picker";

import { useIsMobile } from "@/hooks/use-mobile";

import { type InlineFieldBaseProps } from "./types";
import { RowLabel, resolveInlineField, rowCn } from "./shared";
import { cn } from "@workspace/ui/lib/utils";

export type InlineNumberProps = InlineFieldBaseProps & {
    field?: ControllerRenderProps<FieldValues, string>;
    fieldState?: ControllerFieldState;
    step?: number;
    min?: number;
    max?: number;
    unit?: string;
};

export function InlineNumber({
    field,
    fieldState,
    value,
    onChange,
    name,
    invalid,
    label,
    Icon,
    isLoading,
    step = 1,
    min,
    max,
    unit,
    className,
}: InlineNumberProps) {
    const [open, setOpen] = useState(false);
    const isMobile = useIsMobile();
    const t = useTranslations();
    const inputRef = useRef<HTMLInputElement>(null);
    const resolved = resolveInlineField<number>({
        field,
        fieldState,
        value,
        onChange,
        name,
        invalid,
    });
    const currentValue = typeof resolved.value === "number" ? resolved.value : null;
    const displayValue = currentValue ?? 0;

    const handleIncrement = (e: React.MouseEvent) => {
        e.stopPropagation();
        const base = currentValue ?? 0;
        const next = parseFloat((base + step).toFixed(10));
        if (max === undefined || next <= max) resolved.onChange(next);
    };

    const handleDecrement = (e: React.MouseEvent) => {
        e.stopPropagation();
        const base = currentValue ?? 0;
        const next = parseFloat((base - step).toFixed(10));
        if (min === undefined || next >= min) resolved.onChange(next);
    };

    const row = (
        <div
            data-slot="inline-row"
            data-invalid={resolved.showError}
            className={rowCn(resolved.showError, isLoading, className, true)}
        >
            <RowLabel Icon={Icon} label={label} />
            <div className="flex gap-1.5 items-center text-sm">
                <Button
                    type="button"
                    variant="flat"
                    size="icon-xs"
                    disabled={isLoading || (min !== undefined && displayValue <= min)}
                    onClick={handleDecrement}
                >
                    <Minus className="size-3" strokeWidth={1.5} />
                </Button>
                <input
                    ref={inputRef}
                    key={displayValue}
                    type="number"
                    defaultValue={displayValue}
                    min={min}
                    max={max}
                    step={step}
                    disabled={isLoading}
                    onBlur={(e) => {
                        const val = parseFloat(e.target.value);
                        if (isNaN(val)) return;
                        const clamped = Math.max(min ?? -Infinity, Math.min(max ?? Infinity, val));
                        resolved.onChange(parseFloat(clamped.toFixed(10)));
                    }}
                    className="min-w-8 w-10 text-center bg-transparent outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                />
                <Button
                    type="button"
                    variant="flat"
                    size="icon-xs"
                    disabled={isLoading || (max !== undefined && displayValue >= max)}
                    onClick={handleIncrement}
                >
                    <Plus className="size-3" strokeWidth={1.5} />
                </Button>
            </div>
        </div>
    );

    if (isMobile) {
        return (
            <Drawer open={open} onOpenChange={setOpen}>
                <DrawerTrigger asChild>{row}</DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader className="pb-0">
                        <DrawerTitle>{label}</DrawerTitle>
                    </DrawerHeader>
                    <div className="flex justify-center py-8">
                        <NumberScrollPicker
                            value={displayValue}
                            onChange={(nextValue) => resolved.onChange(nextValue)}
                            min={min ?? 0}
                            max={max ?? 100}
                            step={step}
                            unit={unit}
                            sideItems={3}
                            size="lg"
                        />
                    </div>
                    <DrawerFooter className="p-0">
                        <Button type="button" size="xl" onClick={() => setOpen(false)}>
                            {t("common.actions.confirm")}
                        </Button>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <div
            data-slot="inline-row"
            data-invalid={resolved.showError}
            className={cn(rowCn(resolved.showError, isLoading, className), "cursor-text")}
            onClick={() => inputRef.current?.focus()}
        >
            <RowLabel Icon={Icon} label={label} />
            <div className="flex gap-1.5 items-center text-sm">
                <Button
                    type="button"
                    variant="flat"
                    size="icon-xs"
                    disabled={isLoading || (min !== undefined && displayValue <= min)}
                    onClick={handleDecrement}
                >
                    <Minus className="size-3" strokeWidth={1.5} />
                </Button>
                <input
                    ref={inputRef}
                    key={displayValue}
                    type="number"
                    defaultValue={displayValue}
                    min={min}
                    max={max}
                    step={step}
                    disabled={isLoading}
                    name={resolved.name}
                    onBlur={(e) => {
                        const val = parseFloat(e.target.value);
                        if (isNaN(val)) return;
                        const clamped = Math.max(min ?? -Infinity, Math.min(max ?? Infinity, val));
                        resolved.onChange(parseFloat(clamped.toFixed(10)));
                    }}
                    className="min-w-8 w-10 text-center bg-transparent outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                />
                <Button
                    type="button"
                    variant="flat"
                    size="icon-xs"
                    disabled={isLoading || (max !== undefined && displayValue >= max)}
                    onClick={handleIncrement}
                >
                    <Plus className="size-3" strokeWidth={1.5} />
                </Button>
            </div>
        </div>
    );
}

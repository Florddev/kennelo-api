"use client";

import React, { useState } from "react";
import { AltArrowRight } from "@solar-icons/react";

import {
    Drawer,
    DrawerContent,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from "@workspace/ui/components/drawer";
import { Popover, PopoverContent, PopoverTrigger } from "@workspace/ui/components/popover";
import { cn } from "@workspace/ui/lib/utils";

import { useIsMobile } from "@/hooks/use-mobile";

import { type InlineChoiceFieldProps } from "./types";
import { RowLabel, resolveInlineField, rowCn } from "./shared";

export function InlineCardList({
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
    const [open, setOpen] = useState(false);
    const isMobile = useIsMobile();
    const resolved = resolveInlineField<string>({
        field,
        fieldState,
        value,
        onChange,
        name,
        invalid,
    });
    const selectedOption = options.find((option) => option.value === resolved.value);

    const handleSelect = (value: string) => {
        resolved.onChange(value === resolved.value ? "" : value);
        setOpen(false);
    };

    const trigger = (
        <div
            data-slot="inline-row"
            data-invalid={resolved.showError}
            className={rowCn(resolved.showError, isLoading, className, true)}
        >
            <RowLabel Icon={Icon} label={label} />
            <div className="flex gap-1.5 items-center text-sm shrink-0">
                <span className={cn(!selectedOption && "text-muted-foreground")}>
                    {selectedOption?.label}
                </span>
                <AltArrowRight className="size-3.5" />
            </div>
        </div>
    );

    const grid = (
        <div className="grid grid-cols-2 gap-2 p-4 pb-6">
            {options.map((option) => {
                const isSelected = option.value === resolved.value;
                return (
                    <button
                        key={option.value}
                        type="button"
                        className={cn(
                            "flex flex-col items-start gap-3 rounded-lg border p-3 transition-all",
                            isSelected
                                ? "ring-2 ring-primary bg-primary/5 border-primary"
                                : "border-input hover:border-primary/60",
                        )}
                        onClick={() => handleSelect(option.value)}
                    >
                        {option.visual ?? (option.Icon && <option.Icon className="size-9" />)}
                        <span className="text-sm font-semibold">{option.label}</span>
                    </button>
                );
            })}
        </div>
    );

    if (isMobile) {
        return (
            <Drawer open={open} onOpenChange={setOpen}>
                <DrawerTrigger asChild>{trigger}</DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>{label}</DrawerTitle>
                    </DrawerHeader>
                    <div className="overflow-y-auto">{grid}</div>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent className="p-0 w-80">{grid}</PopoverContent>
        </Popover>
    );
}

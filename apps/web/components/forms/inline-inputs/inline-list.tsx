"use client";

import React, { useState } from "react";
import { AltArrowRight } from "@solar-icons/react";
import { useTranslations } from "next-intl";

import {
    Command,
    CommandEmpty,
    CommandInput,
    CommandItem,
    CommandList,
} from "@workspace/ui/components/command";
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

export function InlineList({
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
    className,
}: InlineChoiceFieldProps) {
    const [open, setOpen] = useState(false);
    const isMobile = useIsMobile();
    const t = useTranslations();
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
                    {selectedOption?.label ?? placeholder}
                </span>
                <AltArrowRight className="size-3.5" />
            </div>
        </div>
    );

    const commandContent = (
        <Command className="gap-2">
            <CommandInput placeholder={t("common.placeholders.search")} />
            <CommandList>
                <CommandEmpty>{t("common.fields.noResults")}</CommandEmpty>
                {options.map((option) => (
                    <CommandItem
                        key={option.value}
                        value={option.value}
                        data-checked={resolved.value === option.value}
                        onSelect={handleSelect}
                    >
                        {option.label}
                    </CommandItem>
                ))}
            </CommandList>
        </Command>
    );

    if (isMobile) {
        return (
            <Drawer open={open} onOpenChange={setOpen}>
                <DrawerTrigger asChild>{trigger}</DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>{label}</DrawerTitle>
                    </DrawerHeader>
                    <div className="p-0">{commandContent}</div>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent className="p-0 w-64">{commandContent}</PopoverContent>
        </Popover>
    );
}

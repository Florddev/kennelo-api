"use client";

import { useState } from "react";
import {
    Control,
    Controller,
    ControllerFieldState,
    ControllerRenderProps,
    FieldValues,
    Path,
} from "react-hook-form";
import { AltArrowRight, type IconProps } from "@solar-icons/react";
import { Minus, Plus } from "lucide-react";
import { useTranslations } from "next-intl";

import { Button } from "@workspace/ui/components/button";
import { Calendar } from "@workspace/ui/components/calendar";
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

export type InlineOption = {
    label: string;
    value: string;
};

export type InlineControllerProps<TFieldValues extends FieldValues> = {
    name: Path<TFieldValues>;
    control: Control<TFieldValues>;
    label: string;
    type?: "text" | "number" | "date" | "list";
    Icon?: React.ComponentType<IconProps>;
    isLoading?: boolean;
    placeholder?: string;
    options?: InlineOption[];
    step?: number;
    min?: number;
    max?: number;
    className?: string;
};

function rowCn(showError: boolean, isLoading?: boolean, extra?: string) {
    return cn(
        "h-12 p-3 md:h-16 md:p-4 border rounded-sm flex justify-between items-center transition-colors",
        showError && "border-destructive",
        isLoading && "opacity-50 pointer-events-none",
        extra,
    );
}

function RowLabel({ Icon, label }: { Icon?: React.ComponentType<IconProps>; label: string }) {
    return (
        <div className="flex gap-2.5 items-center text-sm md:text-base font-semibold shrink-0">
            {Icon && <Icon className="size-5 md:size-6" />}
            <span>{label}</span>
        </div>
    );
}

function shouldShowError(fieldState: ControllerFieldState) {
    return fieldState.invalid && (fieldState.isTouched || fieldState.isDirty);
}

function InlineText({
    field,
    fieldState,
    label,
    Icon,
    placeholder,
    isLoading,
    className,
}: {
    field: ControllerRenderProps<FieldValues, string>;
    fieldState: ControllerFieldState;
    label: string;
    Icon?: React.ComponentType<IconProps>;
    placeholder?: string;
    isLoading?: boolean;
    className?: string;
}) {
    const showError = shouldShowError(fieldState);
    const inputId = `inline-text-${field.name}`;

    return (
        <label
            htmlFor={inputId}
            data-slot="inline-row"
            data-invalid={showError}
            className={cn(rowCn(showError, isLoading, className), "cursor-text")}
        >
            <RowLabel Icon={Icon} label={label} />
            <input
                id={inputId}
                {...field}
                type="text"
                placeholder={placeholder}
                disabled={isLoading}
                className="bg-transparent outline-none text-end ms-2 flex-1 min-w-0 text-sm placeholder:text-muted-foreground"
            />
        </label>
    );
}

function InlineNumber({
    field,
    fieldState,
    label,
    Icon,
    isLoading,
    step = 1,
    min,
    max,
    className,
}: {
    field: ControllerRenderProps<FieldValues, string>;
    fieldState: ControllerFieldState;
    label: string;
    Icon?: React.ComponentType<IconProps>;
    placeholder?: string;
    isLoading?: boolean;
    step?: number;
    min?: number;
    max?: number;
    className?: string;
}) {
    const showError = shouldShowError(fieldState);
    const currentValue = typeof field.value === "number" ? field.value : null;
    const displayValue = currentValue ?? 0;

    const handleIncrement = (e: React.MouseEvent) => {
        e.stopPropagation();
        const base = currentValue ?? 0;
        const next = parseFloat((base + step).toFixed(10));
        if (max === undefined || next <= max) field.onChange(next);
    };

    const handleDecrement = (e: React.MouseEvent) => {
        e.stopPropagation();
        const base = currentValue ?? 0;
        const next = parseFloat((base - step).toFixed(10));
        if (min === undefined || next >= min) field.onChange(next);
    };

    return (
        <div
            data-slot="inline-row"
            data-invalid={showError}
            className={rowCn(showError, isLoading, className)}
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
                <span className="min-w-8 text-center tabular-nums">{displayValue}</span>
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

function InlineDate({
    field,
    fieldState,
    label,
    Icon,
    placeholder,
    isLoading,
    className,
}: {
    field: ControllerRenderProps<FieldValues, string>;
    fieldState: ControllerFieldState;
    label: string;
    Icon?: React.ComponentType<IconProps>;
    placeholder?: string;
    isLoading?: boolean;
    className?: string;
}) {
    const [open, setOpen] = useState(false);
    const isMobile = useIsMobile();
    const t = useTranslations();
    const showError = shouldShowError(fieldState);

    const selectedDate = field.value ? new Date(`${field.value}T00:00:00`) : undefined;
    const formattedDate = selectedDate
        ? new Intl.DateTimeFormat(undefined, {
              year: "numeric",
              month: "long",
              day: "numeric",
          }).format(selectedDate)
        : null;

    const handleDateSelect = (date: Date | undefined) => {
        if (!date) {
            field.onChange("");
        } else {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, "0");
            const d = String(date.getDate()).padStart(2, "0");
            field.onChange(`${y}-${m}-${d}`);
        }
        setOpen(false);
    };

    const trigger = (
        <div
            data-slot="inline-row"
            data-invalid={showError}
            className={cn(rowCn(showError, isLoading, className), "cursor-pointer")}
        >
            <RowLabel Icon={Icon} label={label} />
            <div className="flex gap-1.5 items-center text-sm shrink-0">
                <span className={cn(!formattedDate && "text-muted-foreground")}>
                    {formattedDate ?? placeholder}
                </span>
                <AltArrowRight className="size-3.5" />
            </div>
        </div>
    );

    const calendar = (
        <Calendar
            mode="single"
            captionLayout="dropdown"
            startMonth={new Date(1950, 0)}
            endMonth={new Date()}
            selected={selectedDate}
            onSelect={handleDateSelect}
            disabled={{ after: new Date() }}
        />
    );

    if (isMobile) {
        return (
            <Drawer open={open} onOpenChange={setOpen}>
                <DrawerTrigger asChild>{trigger}</DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>{t("common.fields.selectDate")}</DrawerTitle>
                    </DrawerHeader>
                    <div className="flex justify-center pb-4">{calendar}</div>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent className="w-fit p-0">{calendar}</PopoverContent>
        </Popover>
    );
}

function InlineList({
    field,
    fieldState,
    label,
    Icon,
    placeholder,
    isLoading,
    options = [],
    className,
}: {
    field: ControllerRenderProps<FieldValues, string>;
    fieldState: ControllerFieldState;
    label: string;
    Icon?: React.ComponentType<IconProps>;
    placeholder?: string;
    isLoading?: boolean;
    options?: InlineOption[];
    className?: string;
}) {
    const [open, setOpen] = useState(false);
    const isMobile = useIsMobile();
    const t = useTranslations();
    const showError = shouldShowError(fieldState);
    const selectedOption = options.find((o) => o.value === field.value);

    const handleSelect = (value: string) => {
        field.onChange(value === field.value ? "" : value);
        setOpen(false);
    };

    const trigger = (
        <div
            data-slot="inline-row"
            data-invalid={showError}
            className={cn(rowCn(showError, isLoading, className), "cursor-pointer")}
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
        <Command>
            <CommandInput placeholder={t("common.placeholders.search")} />
            <CommandList>
                <CommandEmpty>{t("common.fields.noResults")}</CommandEmpty>
                {options.map((option) => (
                    <CommandItem
                        key={option.value}
                        value={option.value}
                        data-checked={field.value === option.value}
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
                    <div className="pb-4 px-4">{commandContent}</div>
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

export function InlineController<TFieldValues extends FieldValues>({
    name,
    control,
    label,
    type,
    Icon,
    isLoading,
    placeholder,
    options,
    step,
    min,
    max,
    className,
}: InlineControllerProps<TFieldValues>) {
    return (
        <Controller
            name={name}
            control={control}
            render={({ field, fieldState }) => {
                const f = field as ControllerRenderProps<FieldValues, string>;
                const shared = {
                    field: f,
                    fieldState,
                    label,
                    Icon,
                    placeholder,
                    isLoading,
                    className,
                };

                if (type === "number") {
                    return <InlineNumber {...shared} step={step} min={min} max={max} />;
                }
                if (type === "date") {
                    return <InlineDate {...shared} />;
                }
                if (type === "list") {
                    return <InlineList {...shared} options={options} />;
                }
                return <InlineText {...shared} />;
            }}
        />
    );
}

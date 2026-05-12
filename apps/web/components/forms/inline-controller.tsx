"use client";

import { useRef, useState } from "react";
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
import { useLocale, useTranslations } from "next-intl";

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
    DrawerClose,
    DrawerContent,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from "@workspace/ui/components/drawer";
import { DateScrollPicker } from "@workspace/ui/components/date-scroll-picker";
import { NumberScrollPicker } from "@workspace/ui/components/number-scroll-picker";
import {
    MultipleSelector,
    type Option as SelectorOption,
} from "@workspace/ui/components/multi-select";
import { Popover, PopoverContent, PopoverTrigger } from "@workspace/ui/components/popover";
import { SwitchChecker } from "@workspace/ui/components/switch-checker";
import { cn } from "@workspace/ui/lib/utils";
import { useIsMobile } from "@/hooks/use-mobile";

export type InlineOption = {
    label: string;
    value: string;
    Icon?: React.ComponentType<IconProps>;
};

export type InlineControllerProps<TFieldValues extends FieldValues> = {
    name: Path<TFieldValues>;
    control: Control<TFieldValues>;
    label: string;
    type?:
        | "text"
        | "textarea"
        | "number"
        | "date"
        | "list"
        | "multi-list"
        | "button-list"
        | "boolean";
    Icon?: React.ComponentType<IconProps>;
    isLoading?: boolean;
    placeholder?: string;
    options?: InlineOption[];
    step?: number;
    min?: number;
    max?: number;
    unit?: string;
    allowApproximate?: boolean;
    creatable?: boolean;
    className?: string;
};

function rowCn(showError: boolean, isLoading?: boolean, extra?: string, clickable?: boolean) {
    return cn(
        "h-12 p-3 md:gap-4 md:h-16 md:p-4 border rounded-sm flex justify-between items-center transition-colors",
        showError && "border border-destructive bg-destructive/5 text-destructive",
        isLoading && "pointer-events-none opacity-50",
        clickable && "cursor-pointer",
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

function InlineTextarea({
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
    const textareaId = `inline-textarea-${field.name}`;

    return (
        <label
            htmlFor={textareaId}
            data-slot="inline-row"
            data-invalid={showError}
            className={cn(
                "p-3 md:p-4 border rounded-sm flex flex-col gap-2 transition-colors cursor-text",
                showError && "border-destructive",
                isLoading && "opacity-50 pointer-events-none",
                className,
            )}
        >
            <RowLabel Icon={Icon} label={label} />
            <textarea
                id={textareaId}
                {...field}
                value={field.value ?? ""}
                placeholder={placeholder}
                disabled={isLoading}
                rows={3}
                className="bg-transparent outline-none text-sm placeholder:text-muted-foreground resize-none w-full"
            />
        </label>
    );
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
    unit,
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
    unit?: string;
    className?: string;
}) {
    const [open, setOpen] = useState(false);
    const isMobile = useIsMobile();
    const t = useTranslations();
    const inputRef = useRef<HTMLInputElement>(null);
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

    if (isMobile) {
        const mobileTrigger = (
            <div
                data-slot="inline-row"
                data-invalid={showError}
                className={rowCn(showError, isLoading, className, true)}
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

        return (
            <Drawer open={open} onOpenChange={setOpen}>
                <DrawerTrigger asChild>{mobileTrigger}</DrawerTrigger>
                <DrawerContent>
                    <DrawerHeader className="pb-0">
                        <DrawerTitle>{label}</DrawerTitle>
                    </DrawerHeader>
                    <div className="flex justify-center py-8">
                        <NumberScrollPicker
                            value={displayValue}
                            onChange={(v) => field.onChange(v)}
                            min={min ?? 0}
                            max={max ?? 100}
                            step={step}
                            unit={unit}
                            sideItems={3}
                            size="lg"
                        />
                    </div>
                    <DrawerFooter className="p-0">
                        <DrawerClose asChild>
                            <Button type="button" size="xl">
                                {t("common.actions.confirm")}
                            </Button>
                        </DrawerClose>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <div
            data-slot="inline-row"
            data-invalid={showError}
            className={cn(rowCn(showError, isLoading, className), "cursor-text")}
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
                    onBlur={(e) => {
                        const val = parseFloat(e.target.value);
                        if (isNaN(val)) return;
                        const clamped = Math.max(min ?? -Infinity, Math.min(max ?? Infinity, val));
                        field.onChange(parseFloat(clamped.toFixed(10)));
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

function DateModeToggle({
    mode,
    onChange,
    className,
}: {
    mode: "exact" | "approximate";
    onChange: (mode: "exact" | "approximate") => void;
    className?: string;
}) {
    const t = useTranslations();
    return (
        <div className={cn("flex gap-1 p-1 bg-muted rounded-xl", className)}>
            <Button
                type="button"
                variant={mode === "exact" ? "default" : "ghost"}
                size="sm"
                className="flex-1 text-xs"
                onClick={() => onChange("exact")}
            >
                {t("common.fields.exactDate")}
            </Button>
            <Button
                type="button"
                variant={mode === "approximate" ? "default" : "ghost"}
                size="sm"
                className="flex-1 text-xs"
                onClick={() => onChange("approximate")}
            >
                {t("common.fields.approximateAge")}
            </Button>
        </div>
    );
}

function ageToIso(years: number, months: number): string {
    const now = new Date();
    const d = new Date(now.getFullYear() - years, now.getMonth() - months, now.getDate());
    return [
        String(d.getFullYear()).padStart(4, "0"),
        String(d.getMonth() + 1).padStart(2, "0"),
        String(d.getDate()).padStart(2, "0"),
    ].join("-");
}

function isoToAge(iso: string): { years: number; months: number } {
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

function InlineDate({
    field,
    fieldState,
    label,
    Icon,
    placeholder,
    isLoading,
    allowApproximate,
    className,
}: {
    field: ControllerRenderProps<FieldValues, string>;
    fieldState: ControllerFieldState;
    label: string;
    Icon?: React.ComponentType<IconProps>;
    placeholder?: string;
    isLoading?: boolean;
    allowApproximate?: boolean;
    className?: string;
}) {
    const [open, setOpen] = useState(false);
    const [dateMode, setDateMode] = useState<"exact" | "approximate">("exact");
    const [approxYears, setApproxYears] = useState(0);
    const [approxMonths, setApproxMonths] = useState(0);
    const isMobile = useIsMobile();
    const t = useTranslations();
    const locale = useLocale();
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

    function handleModeChange(mode: "exact" | "approximate") {
        if (mode === "approximate" && field.value) {
            const { years, months } = isoToAge(field.value);
            setApproxYears(years);
            setApproxMonths(months);
        }
        setDateMode(mode);
    }

    const todayIso = new Date().toISOString().split("T")[0];

    const trigger = (
        <div
            data-slot="inline-row"
            data-invalid={showError}
            className={rowCn(showError, isLoading, className, true)}
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

    const approximatePickers = (size: "default" | "lg") => (
        <div className="flex flex-col justify-around w-full gap-4">
            <NumberScrollPicker
                value={approxYears}
                onChange={(v) => {
                    const y = v as number;
                    setApproxYears(y);
                    field.onChange(ageToIso(y, approxMonths));
                }}
                min={0}
                max={25}
                label={t("common.fields.years")}
                sideItems={3}
                size={size}
            />
            <NumberScrollPicker
                value={approxMonths}
                onChange={(v) => {
                    const m = v as number;
                    setApproxMonths(m);
                    field.onChange(ageToIso(approxYears, m));
                }}
                min={0}
                max={11}
                label={t("common.fields.months")}
                sideItems={3}
                size={size}
            />
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
                    <DrawerHeader className="pb-0">
                        <DrawerTitle>{t("common.fields.selectDate")}</DrawerTitle>
                    </DrawerHeader>
                    {allowApproximate && (
                        <div className="p-2">
                            <DateModeToggle mode={dateMode} onChange={handleModeChange} />
                        </div>
                    )}
                    <div className="flex justify-center py-4 px-1">
                        {dateMode === "exact" ? (
                            <DateScrollPicker
                                value={field.value || todayIso}
                                onChange={field.onChange}
                                minYear={1970}
                                maxYear={new Date().getFullYear()}
                                monthFormat="long"
                                locale={locale}
                                labels={{
                                    day: t("common.fields.day"),
                                    month: t("common.fields.month"),
                                    year: t("common.fields.year"),
                                }}
                                orientation="vertical"
                                className="justify-around w-full"
                                size="default"
                            />
                        ) : (
                            approximatePickers("lg")
                        )}
                    </div>
                    <DrawerFooter className="p-0">
                        <DrawerClose asChild>
                            <Button type="button" size="xl">
                                {t("common.actions.confirm")}
                            </Button>
                        </DrawerClose>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent className={cn("p-0", dateMode === "approximate" && "w-72")}>
                {allowApproximate && (
                    <DateModeToggle mode={dateMode} onChange={handleModeChange} className="m-2" />
                )}
                {dateMode === "exact" ? (
                    calendar
                ) : (
                    <div className="py-6 px-4">{approximatePickers("default")}</div>
                )}
            </PopoverContent>
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
            className={rowCn(showError, isLoading, className, true)}
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

function InlineMultiList({
    field,
    fieldState,
    label,
    Icon,
    placeholder,
    isLoading,
    options = [],
    creatable,
    className,
}: {
    field: ControllerRenderProps<FieldValues, string>;
    fieldState: ControllerFieldState;
    label: string;
    Icon?: React.ComponentType<IconProps>;
    placeholder?: string;
    isLoading?: boolean;
    options?: InlineOption[];
    creatable?: boolean;
    className?: string;
}) {
    const t = useTranslations();
    const containerRef = useRef<HTMLDivElement>(null);
    const showError = shouldShowError(fieldState);

    const selectorOptions: SelectorOption[] = options.map((o) => ({
        value: o.value,
        label: o.label,
    }));

    const selectedValues: string[] = Array.isArray(field.value) ? (field.value as string[]) : [];
    const selectedOptions: SelectorOption[] = selectedValues.map(
        (v) => selectorOptions.find((o) => o.value === v) ?? { value: v, label: v },
    );

    return (
        <div
            ref={containerRef}
            data-slot="inline-row"
            data-invalid={showError}
            className={cn(
                "p-3 md:p-4 border rounded-sm flex flex-col gap-2 transition-colors",
                showError && "border-destructive",
                isLoading && "opacity-50 pointer-events-none",
                className,
            )}
            onClick={() => containerRef.current?.querySelector("input")?.focus()}
        >
            <RowLabel Icon={Icon} label={label} />
            <MultipleSelector
                value={selectedOptions}
                options={selectorOptions}
                onChange={(opts) => field.onChange(opts.map((o) => o.value))}
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

function InlineBoolean({
    field,
    fieldState,
    label,
    Icon,
    isLoading,
    className,
}: {
    field: ControllerRenderProps<FieldValues, string>;
    fieldState: ControllerFieldState;
    label: string;
    Icon?: React.ComponentType<IconProps>;
    isLoading?: boolean;
    className?: string;
}) {
    const showError = shouldShowError(fieldState);
    const switchId = `inline-boolean-${field.name}`;

    return (
        <label
            htmlFor={switchId}
            data-slot="inline-row"
            data-invalid={showError}
            className={rowCn(showError, isLoading, className, true)}
        >
            <RowLabel Icon={Icon} label={label} />
            <SwitchChecker
                id={switchId}
                checked={Boolean(field.value)}
                onCheckedChange={field.onChange}
                size={"sm"}
                disabled={isLoading}
            />
        </label>
    );
}

function InlineButtonList({
    field,
    fieldState,
    label,
    Icon,
    isLoading,
    options = [],
    className,
}: {
    field: ControllerRenderProps<FieldValues, string>;
    fieldState: ControllerFieldState;
    label: string;
    Icon?: React.ComponentType<IconProps>;
    isLoading?: boolean;
    options?: InlineOption[];
    className?: string;
}) {
    const showError = shouldShowError(fieldState);

    return (
        <div
            data-slot="inline-row"
            data-invalid={showError}
            className={rowCn(showError, isLoading, className)}
        >
            <RowLabel Icon={Icon} label={label} />
            <div className="flex gap-0.5 items-center">
                {options.map((option) => (
                    <Button
                        key={option.value}
                        type="button"
                        variant={field.value === option.value ? "default" : "flat"}
                        size="sm"
                        disabled={isLoading}
                        onClick={() =>
                            field.onChange(field.value === option.value ? "" : option.value)
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
    unit,
    allowApproximate,
    creatable,
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

                if (type === "textarea") {
                    return <InlineTextarea {...shared} />;
                }
                if (type === "number") {
                    return <InlineNumber {...shared} step={step} min={min} max={max} unit={unit} />;
                }
                if (type === "date") {
                    return <InlineDate {...shared} allowApproximate={allowApproximate} />;
                }
                if (type === "list") {
                    return <InlineList {...shared} options={options} />;
                }
                if (type === "multi-list") {
                    return <InlineMultiList {...shared} options={options} creatable={creatable} />;
                }
                if (type === "button-list") {
                    return <InlineButtonList {...shared} options={options} />;
                }
                if (type === "boolean") {
                    return <InlineBoolean {...shared} />;
                }
                return <InlineText {...shared} />;
            }}
        />
    );
}

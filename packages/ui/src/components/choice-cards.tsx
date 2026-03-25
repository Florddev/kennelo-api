"use client";

import * as React from "react";
import { AlertCircle } from "lucide-react";
import { Checkbox } from "@workspace/ui/components/checkbox";
import { RadioGroup, RadioGroupItem } from "@workspace/ui/components/radio-group";
import { cn } from "@workspace/ui/lib/utils";

export type ChoiceCardOption<TValue extends string> = {
    value: TValue;
    label: React.ReactNode;
    description?: React.ReactNode;
    icon?: React.ComponentType<{ className?: string }>;
    disabled?: boolean;
    className?: string;
};

type ChoiceCardsBaseProps<TValue extends string> = {
    options: ChoiceCardOption<TValue>[];
    error?: React.ReactNode;
    className?: string;
    optionsClassName?: string;
    optionClassName?: string;
    layout?: "list" | "grid";
    render?: (option: ChoiceCardOption<TValue>, isSelected: boolean) => React.ReactNode;
};

type ChoiceCardsSingleProps<TValue extends string> = ChoiceCardsBaseProps<TValue> & {
    mode: "single";
    value: TValue | null;
    onValueChange: (value: TValue) => void;
};

type ChoiceCardsMultipleProps<TValue extends string> = ChoiceCardsBaseProps<TValue> & {
    mode: "multiple";
    value: TValue[];
    onValueChange: (value: TValue[]) => void;
};

export type ChoiceCardsProps<TValue extends string> =
    | ChoiceCardsSingleProps<TValue>
    | ChoiceCardsMultipleProps<TValue>;

function optionCardClass(isSelected: boolean, disabled?: boolean, className?: string) {
    return cn(
        "group/choice-card flex justify-between rounded-lg border py-4 px-5 text-start transition-all gap-1",
        "focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:outline-none focus-visible:ring-[3px]",
        isSelected ? "ring-2 ring-primary bg-primary/5" : "border-stone-200",
        disabled && "pointer-events-none opacity-50",
        className,
    );
}

function optionsLayoutClass(layout?: "list" | "grid", className?: string) {
    if (layout === "grid") {
        return cn("grid grid-cols-2 gap-3", className);
    }

    return cn("flex flex-col gap-4", className);
}

export function ChoiceCardContainer({
    isSelected,
    disabled,
    className,
    children,
}: {
    isSelected: boolean;
    disabled?: boolean;
    className?: string;
    children: React.ReactNode;
}) {
    return <div className={optionCardClass(isSelected, disabled, className)}>{children}</div>;
}

export function ChoiceCardContent({
    className,
    children,
}: {
    className?: string;
    children: React.ReactNode;
}) {
    return <div className={cn("flex flex-col gap-1", className)}>{children}</div>;
}

export function ChoiceCardLabel({
    children,
    className,
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return <span className={cn("font-semibold text-base", className)}>{children}</span>;
}

export function ChoiceCardDescription({
    children,
    className,
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return <span className={cn("text-muted-foreground text-sm", className)}>{children}</span>;
}

export function ChoiceCardIcon({
    icon: Icon,
    className,
    ...rest
}: {
    icon: React.ComponentType<{ className?: string }>;
    className?: string;
}) {
    return <Icon className={cn("size-10 stroke-1 text-primary", className)} {...rest} />;
}

function DefaultChoiceCardContent<TValue extends string>({
    option,
    isSelected,
    className,
}: {
    option: ChoiceCardOption<TValue>;
    isSelected: boolean;
    className?: string;
}) {
    const Icon = option.icon;

    return (
        <ChoiceCardContainer
            isSelected={isSelected}
            disabled={option.disabled}
            className={cn(className, option.className)}
        >
            <ChoiceCardContent>
                <ChoiceCardLabel>{option.label}</ChoiceCardLabel>
                {option.description && (
                    <ChoiceCardDescription>{option.description}</ChoiceCardDescription>
                )}
            </ChoiceCardContent>
            {Icon && <ChoiceCardIcon icon={Icon} />}
        </ChoiceCardContainer>
    );
}

function ChoiceCardsSingle<TValue extends string>({
    options,
    value,
    onValueChange,
    className,
    optionsClassName,
    optionClassName,
    layout = "list",
    render,
}: Omit<ChoiceCardsSingleProps<TValue>, "mode" | "error"> & {
    render?: (option: ChoiceCardOption<TValue>, isSelected: boolean) => React.ReactNode;
}) {
    return (
        <RadioGroup
            data-slot="choice-cards"
            value={value ?? ""}
            onValueChange={(nextValue) => onValueChange(nextValue as TValue)}
            className={cn("w-full", className)}
        >
            <div
                data-slot="choice-cards-options"
                className={optionsLayoutClass(layout, optionsClassName)}
            >
                {options.map((option) => {
                    const id = `choice-${option.value}`;
                    const isSelected = value === option.value;

                    return (
                        <label key={option.value} htmlFor={id} className="cursor-pointer">
                            <div className="sr-only">
                                <RadioGroupItem
                                    id={id}
                                    value={option.value}
                                    disabled={option.disabled}
                                />
                            </div>
                            {render ? (
                                render(option, isSelected)
                            ) : (
                                <DefaultChoiceCardContent
                                    option={option}
                                    isSelected={isSelected}
                                    className={optionClassName}
                                />
                            )}
                        </label>
                    );
                })}
            </div>
        </RadioGroup>
    );
}

function ChoiceCardsMultiple<TValue extends string>({
    options,
    value,
    onValueChange,
    className,
    optionsClassName,
    optionClassName,
    layout = "list",
    render,
}: Omit<ChoiceCardsMultipleProps<TValue>, "mode" | "error"> & {
    render?: (option: ChoiceCardOption<TValue>, isSelected: boolean) => React.ReactNode;
}) {
    const selectedValues = new Set(value);

    const toggleValue = (optionValue: TValue) => {
        if (selectedValues.has(optionValue)) {
            onValueChange(value.filter((current) => current !== optionValue));
            return;
        }

        onValueChange([...value, optionValue]);
    };

    return (
        <div data-slot="choice-cards" className={cn("w-full", className)}>
            <div
                data-slot="choice-cards-options"
                className={optionsLayoutClass(layout, optionsClassName)}
            >
                {options.map((option) => {
                    const id = `choice-${option.value}`;
                    const isSelected = selectedValues.has(option.value);

                    return (
                        <label key={option.value} htmlFor={id} className="cursor-pointer">
                            <div className="sr-only">
                                <Checkbox
                                    id={id}
                                    checked={isSelected}
                                    disabled={option.disabled}
                                    onCheckedChange={() => toggleValue(option.value)}
                                />
                            </div>
                            {render ? (
                                render(option, isSelected)
                            ) : (
                                <DefaultChoiceCardContent
                                    option={option}
                                    isSelected={isSelected}
                                    className={optionClassName}
                                />
                            )}
                        </label>
                    );
                })}
            </div>
        </div>
    );
}

export function ChoiceCards<TValue extends string>(props: ChoiceCardsProps<TValue>) {
    return (
        <div className="flex flex-col gap-3">
            {props.mode === "single" ? (
                <ChoiceCardsSingle
                    options={props.options}
                    value={props.value}
                    onValueChange={props.onValueChange}
                    className={props.className}
                    optionsClassName={props.optionsClassName}
                    optionClassName={props.optionClassName}
                    layout={props.layout}
                    render={props.render}
                />
            ) : (
                <ChoiceCardsMultiple
                    options={props.options}
                    value={props.value}
                    onValueChange={props.onValueChange}
                    className={props.className}
                    optionsClassName={props.optionsClassName}
                    optionClassName={props.optionClassName}
                    layout={props.layout}
                    render={props.render}
                />
            )}

            {props.error && (
                <div className="flex items-center gap-2 rounded-2xl border border-destructive/30 bg-destructive/5 px-4 py-3">
                    <AlertCircle className="size-4 text-destructive shrink-0" />
                    <p className="text-sm text-destructive font-medium">{props.error}</p>
                </div>
            )}
        </div>
    );
}

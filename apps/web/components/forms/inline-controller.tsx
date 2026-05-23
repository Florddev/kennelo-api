"use client";

import { JSX, type ComponentType } from "react";
import {
    Controller,
    type Control,
    type ControllerRenderProps,
    type FieldValues,
    type Path,
} from "react-hook-form";
import { type IconProps } from "@solar-icons/react";

import { Inline, type InlineProps } from "./inline-input";
import { type InlineOption } from "./inline-inputs/types";

export type InlineControllerProps<TFieldValues extends FieldValues> = {
    name: Path<TFieldValues>;
    control: Control<TFieldValues>;
    label: string;
    type?: InlineProps["type"];
    Icon?: ComponentType<IconProps>;
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

export type { InlineOption } from "./inline-inputs/types";
export { BadgeListPicker } from "./inline-inputs/inline-badge-list";
export { InlineBadgeList } from "./inline-inputs/inline-badge-list";
export { InlineBoolean } from "./inline-inputs/inline-boolean";
export { InlineButtonList } from "./inline-inputs/inline-button-list";
export { InlineCardList } from "./inline-inputs/inline-card-list";
export { InlineDate } from "./inline-inputs/inline-date";
export { InlineList } from "./inline-inputs/inline-list";
export { InlineMultiList } from "./inline-inputs/inline-multi-list";
export { InlineNumber } from "./inline-inputs/inline-number";
export { InlineText } from "./inline-inputs/inline-text";
export { InlineTextarea } from "./inline-inputs/inline-textarea";

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
}: InlineControllerProps<TFieldValues>): JSX.Element {
    return (
        <Controller
            name={name}
            control={control}
            render={({ field, fieldState }) => (
                <Inline
                    label={label}
                    type={type}
                    Icon={Icon as ComponentType<IconProps> | undefined}
                    isLoading={isLoading}
                    placeholder={placeholder}
                    options={options}
                    step={step}
                    min={min}
                    max={max}
                    unit={unit}
                    allowApproximate={allowApproximate}
                    creatable={creatable}
                    className={className}
                    field={field as ControllerRenderProps<FieldValues, string>}
                    fieldState={fieldState}
                />
            )}
        />
    );
}

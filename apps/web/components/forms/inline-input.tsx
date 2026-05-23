"use client";

import { type ComponentType, type JSX } from "react";
import { type IconProps } from "@solar-icons/react";
import {
    type ControllerFieldState,
    type ControllerRenderProps,
    type FieldValues,
} from "react-hook-form";

import { InlineBadgeList } from "./inline-inputs/inline-badge-list";
import { InlineBoolean } from "./inline-inputs/inline-boolean";
import { InlineButtonList } from "./inline-inputs/inline-button-list";
import { InlineCardList } from "./inline-inputs/inline-card-list";
import { InlineDate } from "./inline-inputs/inline-date";
import { InlineList } from "./inline-inputs/inline-list";
import { InlineMultiList } from "./inline-inputs/inline-multi-list";
import { InlineNumber } from "./inline-inputs/inline-number";
import { InlineText } from "./inline-inputs/inline-text";
import { InlineTextarea } from "./inline-inputs/inline-textarea";
import {
    type InlineFieldBaseProps,
    type InlineOption,
    type InlineValue,
} from "./inline-inputs/types";

export type InlineProps = InlineFieldBaseProps & {
    type?:
        | "text"
        | "textarea"
        | "number"
        | "date"
        | "list"
        | "multi-list"
        | "button-list"
        | "boolean"
        | "card-list"
        | "badge-list";
    step?: number;
    min?: number;
    max?: number;
    unit?: string;
    allowApproximate?: boolean;
    creatable?: boolean;
    options?: InlineOption[];
    value?: InlineValue;
    onChange?: (value: InlineValue) => void;
    name?: string;
    invalid?: boolean;
    field?: ControllerRenderProps<FieldValues, string>;
    fieldState?: ControllerFieldState;
};

export type { InlineOption } from "./inline-inputs/types";

export function Inline({
    type,
    label,
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
    value,
    onChange,
    name,
    invalid,
    field,
    fieldState,
}: InlineProps): JSX.Element {
    const base = {
        label,
        Icon: Icon as ComponentType<IconProps> | undefined,
        placeholder,
        isLoading,
        className,
        name,
        invalid,
        field,
        fieldState,
        onChange,
    };

    if (type === "textarea") {
        return <InlineTextarea {...base} value={value as string | undefined} />;
    }
    if (type === "number") {
        return (
            <InlineNumber
                {...base}
                value={value as number | undefined}
                step={step}
                min={min}
                max={max}
                unit={unit}
            />
        );
    }
    if (type === "date") {
        return (
            <InlineDate
                {...base}
                value={value as string | undefined}
                allowApproximate={allowApproximate}
            />
        );
    }
    if (type === "list") {
        return <InlineList {...base} value={value as string | undefined} options={options} />;
    }
    if (type === "multi-list") {
        return (
            <InlineMultiList
                {...base}
                value={value as string[] | undefined}
                options={options}
                creatable={creatable}
            />
        );
    }
    if (type === "button-list") {
        return <InlineButtonList {...base} value={value as string | undefined} options={options} />;
    }
    if (type === "boolean") {
        return <InlineBoolean {...base} value={value as boolean | undefined} />;
    }
    if (type === "card-list") {
        return <InlineCardList {...base} value={value as string | undefined} options={options} />;
    }
    if (type === "badge-list") {
        return <InlineBadgeList {...base} value={value as string | undefined} options={options} />;
    }
    return <InlineText {...base} value={value as string | undefined} />;
}

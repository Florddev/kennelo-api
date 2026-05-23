import { type ComponentType, type ReactNode } from "react";
import { type IconProps } from "@solar-icons/react";
import {
    type ControllerFieldState,
    type ControllerRenderProps,
    type FieldValues,
} from "react-hook-form";

export type InlineValue = string | number | boolean | string[] | null | undefined;

export type InlineFieldBridgeProps<TValue extends InlineValue> = {
    field?: ControllerRenderProps<FieldValues, string>;
    fieldState?: ControllerFieldState;
    value?: TValue;
    onChange?: (value: TValue) => void;
    name?: string;
    invalid?: boolean;
};

export type InlineOption = {
    label: string;
    value: string;
    Icon?: ComponentType<IconProps>;
    visual?: ReactNode;
};

export type InlineOptionFieldProps<TValue extends InlineValue = string> =
    InlineFieldBaseProps<TValue> & {
        options?: InlineOption[];
    };

export type InlineChoiceFieldProps = InlineOptionFieldProps<string>;

export type InlineMultiChoiceFieldProps = InlineOptionFieldProps<string[]> & {
    creatable?: boolean;
};

export type InlineFieldBaseProps<TValue extends InlineValue = InlineValue> =
    InlineFieldBridgeProps<TValue> & {
        label: string;
        Icon?: ComponentType<IconProps>;
        placeholder?: string;
        isLoading?: boolean;
        className?: string;
    };
